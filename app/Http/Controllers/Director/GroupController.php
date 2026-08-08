<?php

namespace App\Http\Controllers\Director;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\DirectorGroupResource;
use App\Http\Resources\GroupStudentResource;
use App\Models\Group;
use App\Models\User;
use App\Services\Director\CareerScope;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    use ApiResponse;

    public function index(Request $request, CareerScope $scope): JsonResponse
    {
        $careerIds = $scope->assertHasCareer($request->user());

        $groups = Group::query()
            ->whereIn('career_id', $careerIds)
            ->when($request->query('school_year_id'), fn ($query, $schoolYearId) => $query->where('school_year_id', $schoolYearId))
            ->when($request->query('shift'), fn ($query, $shift) => $query->where('shift', $shift))
            ->withCount(['students as student_count' => fn ($query) => $query->where('active', true)])
            ->get();

        return $this->successResponse('Grupos obtenidos correctamente.', DirectorGroupResource::collection($groups));
    }

    public function show(Request $request, int $group, CareerScope $scope): JsonResponse
    {
        $groupModel = $this->findGroup($request->user(), $group, $scope)
            ->load('academicTutors.user');

        $groupModel->attendance_summary = $groupModel->students()
            ->join('attendances', 'attendances.student_id', '=', 'users.id')
            ->selectRaw('attendances.status, count(*) as total')
            ->groupBy('attendances.status')
            ->pluck('total', 'status');

        return $this->successResponse('Grupo obtenido correctamente.', new DirectorGroupResource($groupModel));
    }

    public function students(Request $request, int $group, CareerScope $scope): JsonResponse
    {
        $groupModel = $this->findGroup($request->user(), $group, $scope);

        $students = $groupModel->students()->active()->get();

        return $this->successResponse('Alumnos obtenidos correctamente.', GroupStudentResource::collection($students));
    }

    public function schedule(Request $request, int $group, CareerScope $scope): JsonResponse
    {
        $groupModel = $this->findGroup($request->user(), $group, $scope);

        $schedules = $groupModel->schedules()
            ->where('is_active', true)
            ->with(['subject', 'teacher', 'classroom'])
            ->get()
            ->map(fn ($schedule) => [
                'schedule_id' => $schedule->id,
                'subject' => ['id' => $schedule->subject->id, 'name' => $schedule->subject->name],
                'teacher' => ['id' => $schedule->teacher->id, 'full_name' => $schedule->teacher->fullName()],
                'classroom' => ['name' => $schedule->classroom->name, 'building' => $schedule->classroom->building],
                'day_of_week' => $schedule->day_of_week,
                'start_time' => substr((string) $schedule->start_time, 0, 5),
                'end_time' => substr((string) $schedule->end_time, 0, 5),
            ])
            ->values();

        return $this->successResponse('Horario obtenido correctamente.', $schedules);
    }

    private function findGroup(User $director, int $id, CareerScope $scope): Group
    {
        $group = Group::find($id);

        if ($group === null) {
            throw ApiException::notFound('El grupo solicitado no existe.', 'GRP02');
        }

        $careerIds = $scope->careerIds($director);

        if (! $careerIds->contains($group->career_id)) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }

        return $group;
    }
}
