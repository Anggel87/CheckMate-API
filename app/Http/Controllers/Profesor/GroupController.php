<?php

namespace App\Http\Controllers\Profesor;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\GroupStudentResource;
use App\Http\Resources\TeacherGroupResource;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $schoolYear = $this->resolveSchoolYear($request);

        $groups = Group::query()
            ->whereHas('schedules', fn ($query) => $query
                ->where('teacher_id', $request->user()->id)
                ->where('is_active', true)
                ->where('school_year_id', $schoolYear->id))
            ->with('career')
            ->withCount(['students as student_count' => fn ($query) => $query->where('active', true)])
            ->get();

        return $this->successResponse('Grupos obtenidos correctamente.', TeacherGroupResource::collection($groups));
    }

    public function students(Request $request, int $group): JsonResponse
    {
        $groupModel = Group::find($group);

        if ($groupModel === null) {
            throw ApiException::notFound('El grupo solicitado no existe.', 'GRP02');
        }

        $this->assertTeachesGroup($request->user()->id, $groupModel->id);

        $students = $groupModel->students()->active()->get();

        return $this->successResponse('Alumnos obtenidos correctamente.', GroupStudentResource::collection($students));
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

    private function assertTeachesGroup(int $teacherId, int $groupId): void
    {
        $teaches = Schedule::query()
            ->where('teacher_id', $teacherId)
            ->where('group_id', $groupId)
            ->where('is_active', true)
            ->exists();

        if (! $teaches) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }
    }
}
