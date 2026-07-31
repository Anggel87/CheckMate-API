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
        $schedules = $this->activeSchedulesForGroup($request->user()->group_id)
            ->with(['subject', 'teacher'])
            ->get()
            ->groupBy('subject_id');

        $subjects = $schedules->map(fn ($group) => (object) [
            'subject' => $group->first()->subject,
            'schedules' => $group,
        ])->values();

        return $this->successResponse('Materias obtenidas correctamente.', SubjectResource::collection($subjects));
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
            ->with('justification')
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
