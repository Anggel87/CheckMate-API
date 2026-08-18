<?php

namespace App\Http\Controllers\Profesor;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profesor\StoreStudentNotificationRequest;
use App\Http\Resources\JustificationResource;
use App\Http\Resources\StudentAttendanceResource;
use App\Http\Resources\StudentProfileResource;
use App\Models\Justification;
use App\Models\Schedule;
use App\Models\User;
use App\Services\NotificationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    use ApiResponse;

    public function show(Request $request, int $student): JsonResponse
    {
        $studentModel = $this->findStudent($request->user(), $student);

        $studentModel->load(['group.career', 'group.academicTutors.user', 'tutors']);

        return $this->successResponse('Alumno obtenido correctamente.', new StudentProfileResource($studentModel));
    }

    public function attendance(Request $request, int $student): JsonResponse
    {
        $user = $request->user();
        $studentModel = $this->findStudent($user, $student);
        $isAcademicTutor = $user->hasRole('tutor_academico');

        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        if ($dateFrom !== null && $dateTo !== null && $dateTo < $dateFrom) {
            throw ApiException::unprocessable('El rango de fechas es inválido.', 'VAL02');
        }

        $attendances = $studentModel->attendances()
            // El tutor academico da seguimiento a todas las materias del alumno, no solo
            // a la suya (si es que da clase); el profesor solo ve su propia materia.
            ->whereHas('schedule', fn ($query) => $query
                ->when(! $isAcademicTutor, fn ($q) => $q->where('teacher_id', $user->id))
                ->when($request->query('subject_id'), fn ($q, $subjectId) => $q->where('subject_id', $subjectId)))
            ->when($dateFrom, fn ($query, $date) => $query->whereDate('registered_at', '>=', $date))
            ->when($dateTo, fn ($query, $date) => $query->whereDate('registered_at', '<=', $date))
            ->with(['schedule.subject', 'classSession'])
            ->latest('registered_at')
            ->get();

        return $this->successResponse('Asistencias obtenidas correctamente.', StudentAttendanceResource::collection($attendances));
    }

    public function justifications(Request $request, int $student): JsonResponse
    {
        $user = $request->user();
        $studentModel = $this->findStudent($user, $student);
        $isAcademicTutor = $user->hasRole('tutor_academico');

        $justifications = Justification::query()
            ->whereHas('attendance', fn ($query) => $query
                ->where('student_id', $studentModel->id)
                ->whereHas('schedule', fn ($scheduleQuery) => $scheduleQuery
                    ->when(! $isAcademicTutor, fn ($q) => $q->where('teacher_id', $user->id))))
            ->with(['attendance.schedule.subject', 'attendance.schedule.teacher', 'reviewedBy'])
            ->latest()
            ->get();

        return $this->successResponse('Justificantes obtenidos correctamente.', JustificationResource::collection($justifications));
    }

    public function notify(StoreStudentNotificationRequest $request, NotificationService $service, int $student): JsonResponse
    {
        $studentModel = $this->findStudent($request->user(), $student);
        $data = $request->validated();

        $created = $service->broadcast($studentModel, 'AVISO', $data['title'], $data['message'], $request->user()->id);

        if ($created === []) {
            throw ApiException::unprocessable('El alumno no tiene tutores disponibles para notificar.', 'NOT02');
        }

        $first = $created[0];

        return $this->successResponse('Aviso enviado correctamente.', [
            'id' => $first->id,
            'title' => $data['title'],
            'recipients_count' => count($created),
            'sent_at' => $first->sent_at?->toIso8601String(),
        ], 201);
    }

    private function findStudent(User $user, int $studentId): User
    {
        $student = User::find($studentId);

        if ($student === null || ! $student->hasRole('alumno')) {
            throw ApiException::notFound('El usuario solicitado no existe.', 'USR01');
        }

        if (! $this->hasAccessToGroup($user, $student->group_id)) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }

        return $student;
    }

    /**
     * El profesor necesita dar clase en el grupo; el tutor academico necesita tener ese
     * grupo asignado como academic tutor, sin importar si ademas da alguna materia ahi.
     */
    private function hasAccessToGroup(User $user, ?int $groupId): bool
    {
        if ($user->hasRole('tutor_academico')) {
            return $user->academicTutor?->activeGroups()->where('groups.id', $groupId)->exists() ?? false;
        }

        return Schedule::query()
            ->where('teacher_id', $user->id)
            ->where('group_id', $groupId)
            ->where('is_active', true)
            ->exists();
    }
}
