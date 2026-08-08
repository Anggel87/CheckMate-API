<?php

namespace App\Http\Controllers\Director;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\DirectorTeacherResource;
use App\Models\ClassSession;
use App\Models\Schedule;
use App\Models\User;
use App\Services\Director\CareerScope;
use App\Support\DayOfWeek;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TeacherController extends Controller
{
    use ApiResponse;

    public function index(Request $request, CareerScope $scope): JsonResponse
    {
        $careerIds = $scope->assertHasCareer($request->user());

        $teachers = User::query()
            ->whereIn('id', $scope->teacherIds($careerIds))
            ->with('role')
            ->withCount('schedules')
            ->get();

        return $this->successResponse('Profesores obtenidos correctamente.', DirectorTeacherResource::collection($teachers));
    }

    public function show(Request $request, int $teacher, CareerScope $scope): JsonResponse
    {
        $teacherModel = $this->findTeacher($request->user(), $teacher, $scope)
            ->load(['role', 'schedules.subject', 'schedules.group']);

        return $this->successResponse('Profesor obtenido correctamente.', new DirectorTeacherResource($teacherModel));
    }

    public function classAttendance(Request $request, int $teacher, CareerScope $scope): JsonResponse
    {
        $teacherModel = $this->findTeacher($request->user(), $teacher, $scope);

        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        if ($dateFrom !== null && $dateTo !== null && $dateTo < $dateFrom) {
            throw ApiException::unprocessable('El rango de fechas es inválido.', 'VAL02');
        }

        $from = $dateFrom !== null ? Carbon::parse($dateFrom) : Carbon::now(config('app.timezone'))->subDays(6);
        $to = $dateTo !== null ? Carbon::parse($dateTo) : Carbon::now(config('app.timezone'));

        $schedules = Schedule::where('teacher_id', $teacherModel->id)
            ->where('is_active', true)
            ->when($request->query('schedule_id'), fn ($query, $scheduleId) => $query->where('id', $scheduleId))
            ->with(['subject', 'group'])
            ->get();

        $rows = [];

        for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
            foreach ($schedules as $schedule) {
                if ($schedule->day_of_week !== DayOfWeek::fromCarbon($date)) {
                    continue;
                }

                $session = ClassSession::where('schedule_id', $schedule->id)
                    ->whereDate('date', $date->format('Y-m-d'))
                    ->whereIn('status', ['ABIERTA', 'CERRADA'])
                    ->first();

                $rows[] = [
                    'date' => $date->format('Y-m-d'),
                    'group' => $schedule->group->grade.'-'.$schedule->group->section,
                    'subject' => $schedule->subject->name,
                    'scheduled_start' => substr((string) $schedule->start_time, 0, 5),
                    'session_opened' => $session !== null,
                    'opened_at' => $session?->opened_at?->toIso8601String(),
                ];
            }
        }

        return $this->successResponse('Asistencia de clases obtenida correctamente.', $rows);
    }

    private function findTeacher(User $director, int $id, CareerScope $scope): User
    {
        $teacher = User::find($id);

        if ($teacher === null) {
            throw ApiException::notFound('El usuario solicitado no existe.', 'USR01');
        }

        $careerIds = $scope->careerIds($director);

        if (! $scope->teacherIds($careerIds)->contains($teacher->id)) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }

        return $teacher;
    }
}
