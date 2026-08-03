<?php

namespace App\Http\Controllers\Profesor;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\JustificationResource;
use App\Http\Resources\StudentAttendanceResource;
use App\Http\Resources\StudentProfileResource;
use App\Models\Justification;
use App\Models\Schedule;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    use ApiResponse;

    public function show(Request $request, int $student): JsonResponse
    {
        $studentModel = $this->findStudent($request->user()->id, $student);

        $studentModel->load('group.career');

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
