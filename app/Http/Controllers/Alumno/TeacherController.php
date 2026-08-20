<?php

namespace App\Http\Controllers\Alumno;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\StudentTeacherDetailResource;
use App\Http\Resources\StudentTeacherResource;
use App\Models\Schedule;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user()->load('group.academicTutors');

        $tutorUserId = $user->group?->academicTutors
            ->firstWhere('pivot.is_active', true)?->user_id;

        $teachers = $this->activeSchedulesForGroup($user->group_id)
            ->with(['teacher', 'subject'])
            ->get()
            ->groupBy('teacher_id')
            ->map(fn ($schedules) => (object) [
                'teacher' => $schedules->first()->teacher,
                'subjects' => $schedules->pluck('subject')->unique('id')->values(),
                'isTutor' => $schedules->first()->teacher_id === $tutorUserId,
            ])
            ->values();

        return $this->successResponse('Profesores obtenidos correctamente.', StudentTeacherResource::collection($teachers));
    }

    /**
     * Solo se puede consultar a un profesor que efectivamente le da clase al
     * alumno (tiene un horario activo compartido con su grupo); de lo
     * contrario no tiene forma de saber su correo, así que se trata como
     * PERM01 en vez de un 404 "no existe".
     */
    public function show(Request $request, int $teacher): JsonResponse
    {
        $user = $request->user()->load('group.academicTutors');

        $tutorUserId = $user->group?->academicTutors
            ->firstWhere('pivot.is_active', true)?->user_id;

        $schedules = $this->activeSchedulesForGroup($user->group_id)
            ->where('teacher_id', $teacher)
            ->with(['teacher', 'subject', 'classroom'])
            ->get();

        if ($schedules->isEmpty()) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }

        $data = (object) [
            'teacher' => $schedules->first()->teacher,
            'subjects' => $schedules->pluck('subject')->unique('id')->values(),
            'isTutor' => $teacher === $tutorUserId,
            'schedules' => $schedules,
        ];

        return $this->successResponse('Profesor obtenido correctamente.', new StudentTeacherDetailResource($data));
    }

    private function activeSchedulesForGroup(?int $groupId): Builder
    {
        return Schedule::query()
            ->where('group_id', $groupId)
            ->where('is_active', true)
            ->whereHas('schoolYear', fn ($query) => $query->where('status', 'ACTIVO'));
    }
}
