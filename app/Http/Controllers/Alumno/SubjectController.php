<?php

namespace App\Http\Controllers\Alumno;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\AttendanceRecordResource;
use App\Http\Resources\SubjectDetailResource;
use App\Http\Resources\SubjectResource;
use App\Models\Schedule;
use App\Models\Subject;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $schedules = $this->activeSchedulesForGroup($user->group_id)
            ->with(['subject', 'teacher'])
            ->get()
            ->groupBy('subject_id');

        $summariesBySubject = $user->attendances()
            ->join('schedules', 'schedules.id', '=', 'attendances.schedule_id')
            ->whereIn('schedules.subject_id', $schedules->keys())
            ->selectRaw('schedules.subject_id, attendances.status, count(*) as total')
            ->groupBy('schedules.subject_id', 'attendances.status')
            ->get()
            ->groupBy('subject_id');

        $subjects = $schedules->map(function ($group, $subjectId) use ($summariesBySubject) {
            $counts = $summariesBySubject->get($subjectId, collect())->pluck('total', 'status');

            return (object) [
                'subject' => $group->first()->subject,
                'schedules' => $group,
                'attendanceSummary' => [
                    'on_time' => (int) ($counts['PRESENTE'] ?? 0),
                    'late' => (int) ($counts['RETARDO'] ?? 0),
                    'absent' => (int) ($counts['FALTA'] ?? 0),
                ],
            ];
        })->values();

        return $this->successResponse('Materias obtenidas correctamente.', SubjectResource::collection($subjects));
    }

    /**
     * Returns every attendance record across all of the student's subjects in a single
     * query, so the frontend doesn't need to request each subject's history separately.
     */
    public function allAttendance(Request $request): JsonResponse
    {
        $user = $request->user();
        $scheduleIds = $this->activeSchedulesForGroup($user->group_id)->pluck('id');

        $attendances = $user->attendances()
            ->whereIn('schedule_id', $scheduleIds)
            ->with(['schedule', 'justification', 'claims'])
            ->latest('registered_at')
            ->get();

        return $this->successResponse('Asistencias obtenidas correctamente.', AttendanceRecordResource::collection($attendances));
    }

    public function show(Request $request, Subject $subject): JsonResponse
    {
        $user = $request->user();

        $schedules = $this->activeSchedulesForGroup($user->group_id)
            ->where('subject_id', $subject->id)
            ->with(['teacher', 'classroom'])
            ->get();

        if ($schedules->isEmpty()) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }

        $counts = $user->attendances()
            ->whereHas('schedule', fn ($query) => $query->where('subject_id', $subject->id))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $summary = (object) [
            'subject' => $subject,
            'schedules' => $schedules,
            'attendanceSummary' => [
                'on_time' => (int) ($counts['PRESENTE'] ?? 0),
                'late' => (int) ($counts['RETARDO'] ?? 0),
                'absent' => (int) ($counts['FALTA'] ?? 0),
            ],
        ];

        return $this->successResponse('Materia obtenida correctamente.', new SubjectDetailResource($summary));
    }

    public function attendance(Request $request, Subject $subject): JsonResponse
    {
        $user = $request->user();

        $enrolled = $this->activeSchedulesForGroup($user->group_id)
            ->where('subject_id', $subject->id)
            ->exists();

        if (! $enrolled) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }

        $attendances = $user->attendances()
            ->whereHas('schedule', fn ($query) => $query->where('subject_id', $subject->id))
            ->with(['schedule', 'justification', 'claims'])
            ->latest('registered_at')
            ->get();

        return $this->successResponse('Asistencias obtenidas correctamente.', AttendanceRecordResource::collection($attendances));
    }

    private function activeSchedulesForGroup(?int $groupId): Builder
    {
        return Schedule::query()
            ->where('group_id', $groupId)
            ->where('is_active', true)
            ->whereHas('schoolYear', fn ($query) => $query->where('status', 'ACTIVO'));
    }
}
