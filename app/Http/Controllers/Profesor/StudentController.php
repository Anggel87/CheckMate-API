<?php

namespace App\Http\Controllers\Profesor;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profesor\StoreStudentNotificationRequest;
use App\Http\Requests\Profesor\StoreTutorRequest;
use App\Http\Resources\JustificationResource;
use App\Http\Resources\StudentAttendanceResource;
use App\Http\Resources\StudentProfileResource;
use App\Models\Justification;
use App\Models\Schedule;
use App\Models\Tutor;
use App\Models\User;
use App\Services\NotificationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    use ApiResponse;

    public function show(Request $request, int $student): JsonResponse
    {
        $studentModel = $this->findStudent($request->user()->id, $student);

        $studentModel->load(['group.career', 'group.academicTutors.user', 'tutors']);

        return $this->successResponse('Alumno obtenido correctamente.', new StudentProfileResource($studentModel));
    }

    public function attendance(Request $request, int $student): JsonResponse
    {
        $studentModel = $this->findStudent($request->user()->id, $student);
        $teacherId = $request->user()->id;

        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        if ($dateFrom !== null && $dateTo !== null && $dateTo < $dateFrom) {
            throw ApiException::unprocessable('El rango de fechas es inválido.', 'VAL02');
        }

        $attendances = $studentModel->attendances()
            ->whereHas('schedule', fn ($query) => $query
                ->where('teacher_id', $teacherId)
                ->when($request->query('subject_id'), fn ($q, $subjectId) => $q->where('subject_id', $subjectId)))
            ->when($dateFrom, fn ($query, $date) => $query->whereDate('registered_at', '>=', $date))
            ->when($dateTo, fn ($query, $date) => $query->whereDate('registered_at', '<=', $date))
            ->with('schedule.subject')
            ->latest('registered_at')
            ->get();

        return $this->successResponse('Asistencias obtenidas correctamente.', StudentAttendanceResource::collection($attendances));
    }

    public function justifications(Request $request, int $student): JsonResponse
    {
        $studentModel = $this->findStudent($request->user()->id, $student);
        $teacherId = $request->user()->id;

        $justifications = Justification::query()
            ->whereHas('attendance', fn ($query) => $query
                ->where('student_id', $studentModel->id)
                ->whereHas('schedule', fn ($scheduleQuery) => $scheduleQuery->where('teacher_id', $teacherId)))
            ->with('attendance.schedule.subject')
            ->latest()
            ->get();

        return $this->successResponse('Justificantes obtenidos correctamente.', JustificationResource::collection($justifications));
    }

    public function addTutor(StoreTutorRequest $request, int $student): JsonResponse
    {
        $studentModel = $this->findStudent($request->user()->id, $student);
        $data = $request->validated();

        $tutor = Tutor::create([
            'first_name' => $data['first_name'],
            'second_name' => $data['second_name'] ?? null,
            'first_surname' => $data['first_surname'],
            'second_surname' => $data['second_surname'],
            'phone' => $data['phone'],
            'is_active' => true,
        ]);

        if ($data['is_primary'] ?? false) {
            DB::table('student_tutor')->where('student_id', $studentModel->id)->update(['is_primary' => false]);
        }

        $studentModel->tutors()->attach($tutor->id, [
            'relationship' => $data['relationship'],
            'is_primary' => $data['is_primary'] ?? false,
            'receives_notifications' => $data['receives_notifications'] ?? true,
        ]);

        $studentModel->load(['group.career', 'group.academicTutors.user', 'tutors']);

        return $this->successResponse('Tutor agregado correctamente.', new StudentProfileResource($studentModel), 201);
    }

    public function notify(StoreStudentNotificationRequest $request, NotificationService $service, int $student): JsonResponse
    {
        $studentModel = $this->findStudent($request->user()->id, $student);
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

    private function findStudent(int $teacherId, int $studentId): User
    {
        $student = User::find($studentId);

        if ($student === null || ! $student->hasRole('alumno')) {
            throw ApiException::notFound('El usuario solicitado no existe.', 'USR01');
        }

        $teaches = Schedule::query()
            ->where('teacher_id', $teacherId)
            ->where('group_id', $student->group_id)
            ->where('is_active', true)
            ->exists();

        if (! $teaches) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }

        return $student;
    }
}
