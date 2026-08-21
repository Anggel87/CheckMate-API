<?php

namespace App\Http\Controllers\Director;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\GroupStudentResource;
use App\Http\Resources\JustificationResource;
use App\Http\Resources\StudentAttendanceResource;
use App\Http\Resources\StudentProfileResource;
use App\Models\Justification;
use App\Models\User;
use App\Services\Director\CareerScope;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    use ApiResponse;

    /**
     * Roster completo del director (todos sus grupos/carreras juntos), usado por el
     * selector de destinatarios al componer una notificacion.
     */
    public function index(Request $request, CareerScope $scope): JsonResponse
    {
        $careerIds = $scope->assertHasCareer($request->user());
        $studentIds = $scope->studentIds($careerIds);

        $students = User::query()->whereKey($studentIds)->where('active', true)->get();

        return $this->successResponse('Alumnos obtenidos correctamente.', GroupStudentResource::collection($students));
    }

    public function show(Request $request, int $student, CareerScope $scope): JsonResponse
    {
        $studentModel = $this->findStudent($request->user(), $student, $scope);

        $studentModel->load('group.career');

        return $this->successResponse('Alumno obtenido correctamente.', new StudentProfileResource($studentModel));
    }

    public function attendance(Request $request, int $student, CareerScope $scope): JsonResponse
    {
        $studentModel = $this->findStudent($request->user(), $student, $scope);

        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        if ($dateFrom !== null && $dateTo !== null && $dateTo < $dateFrom) {
            throw ApiException::unprocessable('El rango de fechas es inválido.', 'VAL02');
        }

        $attendances = $studentModel->attendances()
            ->when($dateFrom, fn ($query, $date) => $query->whereDate('registered_at', '>=', $date))
            ->when($dateTo, fn ($query, $date) => $query->whereDate('registered_at', '<=', $date))
            ->with('schedule.subject')
            ->latest('registered_at')
            ->get();

        return $this->successResponse('Asistencias obtenidas correctamente.', StudentAttendanceResource::collection($attendances));
    }

    public function justifications(Request $request, int $student, CareerScope $scope): JsonResponse
    {
        $studentModel = $this->findStudent($request->user(), $student, $scope);

        $justifications = Justification::query()
            ->whereHas('attendance', fn ($query) => $query->where('student_id', $studentModel->id))
            ->with(['attendance.schedule.subject', 'attendance.schedule.teacher', 'reviewedBy'])
            ->latest()
            ->get();

        return $this->successResponse('Justificantes obtenidos correctamente.', JustificationResource::collection($justifications));
    }

    private function findStudent(User $director, int $studentId, CareerScope $scope): User
    {
        $student = User::find($studentId);

        if ($student === null || ! $student->hasRole('alumno')) {
            throw ApiException::notFound('El usuario solicitado no existe.', 'USR01');
        }

        $careerIds = $scope->careerIds($director);

        if (! $scope->studentIds($careerIds)->contains($student->id)) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }

        return $student;
    }
}
