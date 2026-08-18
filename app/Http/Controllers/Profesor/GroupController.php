<?php

namespace App\Http\Controllers\Profesor;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\GroupStudentResource;
use App\Http\Resources\TeacherGroupResource;
use App\Models\Attendance;
use App\Models\Group;
use App\Models\Justification;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;

class GroupController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $schoolYear = $this->resolveSchoolYear($request);
        $user = $request->user();
        $isAcademicTutor = $user->hasRole('tutor_academico');

        $groups = Group::query()
            ->when(
                $isAcademicTutor,
                fn ($query) => $query->whereIn('id', $this->tutorGroupIds($user)),
                fn ($query) => $query->whereHas('schedules', fn ($q) => $q
                    ->where('teacher_id', $user->id)
                    ->where('is_active', true)
                    ->where('school_year_id', $schoolYear->id)),
            )
            ->with('career')
            ->withCount(['students as student_count' => fn ($query) => $query->where('active', true)])
            ->get();

        return $this->successResponse('Grupos obtenidos correctamente.', TeacherGroupResource::collection($groups));
    }

    public function students(Request $request, int $group): JsonResponse
    {
        $user = $request->user();
        $groupModel = Group::find($group);

        if ($groupModel === null) {
            throw ApiException::notFound('El grupo solicitado no existe.', 'GRP02');
        }

        $this->assertHasAccessToGroup($user, $groupModel->id);

        $students = $groupModel->students()->active()->get();

        $this->attachAttendanceSummary($students, $user);

        return $this->successResponse('Alumnos obtenidos correctamente.', GroupStudentResource::collection($students));
    }

    /**
     * Attaches per-student attendance/justification aggregates computed with a handful of
     * grouped queries, instead of requiring one HTTP round-trip per student to build them.
     */
    private function attachAttendanceSummary(Collection $students, User $user): void
    {
        $studentIds = $students->pluck('id');

        if ($studentIds->isEmpty()) {
            return;
        }

        $isAcademicTutor = $user->hasRole('tutor_academico');
        $today = now()->toDateString();
        $weekStart = now()->startOfWeek(Carbon::MONDAY)->toDateString();
        $weekEnd = now()->endOfWeek(Carbon::SUNDAY)->toDateString();

        // El tutor academico da seguimiento a todas las materias del grupo, no solo a la
        // suya (si es que da clase); el profesor solo ve su propia materia.
        $scopeToTeacher = fn ($query) => $query->when(
            ! $isAcademicTutor,
            fn ($q) => $q->where('teacher_id', $user->id),
        );

        $totalsByStudent = Attendance::query()
            ->whereIn('student_id', $studentIds)
            ->whereHas('schedule', $scopeToTeacher)
            ->selectRaw('student_id, status, count(*) as total')
            ->groupBy('student_id', 'status')
            ->get()
            ->groupBy('student_id');

        $todayByStudent = Attendance::query()
            ->whereIn('student_id', $studentIds)
            ->whereHas('schedule', $scopeToTeacher)
            ->whereDate('registered_at', $today)
            ->selectRaw('student_id, status, count(*) as total')
            ->groupBy('student_id', 'status')
            ->get()
            ->groupBy('student_id');

        $weeklyAbsencesByStudent = Attendance::query()
            ->whereIn('student_id', $studentIds)
            ->where('status', 'FALTA')
            ->whereHas('schedule', $scopeToTeacher)
            ->whereBetween('registered_at', ["{$weekStart} 00:00:00", "{$weekEnd} 23:59:59"])
            ->selectRaw('student_id, count(*) as total')
            ->groupBy('student_id')
            ->pluck('total', 'student_id');

        $justificationsByStudent = Justification::query()
            ->join('attendances', 'justifications.attendance_id', '=', 'attendances.id')
            ->join('schedules', 'attendances.schedule_id', '=', 'schedules.id')
            ->whereIn('attendances.student_id', $studentIds)
            ->when(! $isAcademicTutor, fn ($query) => $query->where('schedules.teacher_id', $user->id))
            ->selectRaw('attendances.student_id as student_id, count(*) as total')
            ->groupBy('attendances.student_id')
            ->pluck('total', 'student_id');

        $students->each(function ($student) use ($totalsByStudent, $todayByStudent, $weeklyAbsencesByStudent, $justificationsByStudent): void {
            $counts = ($totalsByStudent->get($student->id) ?? collect())->pluck('total', 'status');
            $present = (int) $counts->get('PRESENTE', 0);
            $late = (int) $counts->get('RETARDO', 0);
            $absent = (int) $counts->get('FALTA', 0);
            $justified = (int) $counts->get('JUSTIFICADA', 0);
            $total = $present + $late + $absent + $justified;

            $todayCounts = ($todayByStudent->get($student->id) ?? collect())->pluck('total', 'status');

            $student->attendance_rate = $total > 0 ? (int) round((($present + $justified) / $total) * 100) : 0;
            $student->absence_count = $absent;
            $student->late_count = $late;
            $student->justification_count = (int) ($justificationsByStudent->get($student->id) ?? 0);
            $student->today_present_count = (int) $todayCounts->get('PRESENTE', 0) + (int) $todayCounts->get('RETARDO', 0);
            $student->today_absent_count = (int) $todayCounts->get('FALTA', 0);
            $student->weekly_absence_count = (int) ($weeklyAbsencesByStudent->get($student->id) ?? 0);
        });
    }

    private function resolveSchoolYear(Request $request): SchoolYear
    {
        $schoolYearId = $request->query('school_year_id');

        if ($schoolYearId !== null) {
            $schoolYear = SchoolYear::find($schoolYearId);

            if ($schoolYear === null) {
                throw ApiException::notFound('El año escolar indicado no existe.', 'GRP01');
            }

            return $schoolYear;
        }

        $schoolYear = SchoolYear::where('status', 'ACTIVO')->first();

        if ($schoolYear === null) {
            throw ApiException::notFound('El año escolar indicado no existe.', 'GRP01');
        }

        return $schoolYear;
    }

    private function assertHasAccessToGroup(User $user, int $groupId): void
    {
        if ($user->hasRole('tutor_academico')) {
            $hasAccess = $this->tutorGroupIds($user)->contains($groupId);
        } else {
            $hasAccess = Schedule::query()
                ->where('teacher_id', $user->id)
                ->where('group_id', $groupId)
                ->where('is_active', true)
                ->exists();
        }

        if (! $hasAccess) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }
    }

    /**
     * @return SupportCollection<int, int>
     */
    private function tutorGroupIds(User $user): SupportCollection
    {
        return $user->academicTutor?->activeGroups()->pluck('groups.id') ?? collect();
    }
}
