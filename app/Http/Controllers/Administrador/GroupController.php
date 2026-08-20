<?php

namespace App\Http\Controllers\Administrador;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\RequiresDeletionConfirmation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administrador\StoreGroupRequest;
use App\Http\Requests\Administrador\UpdateGroupRequest;
use App\Http\Resources\AdminGroupResource;
use App\Models\Career;
use App\Models\Group;
use App\Models\SchoolYear;
use App\Services\AuditLogger;
use App\Traits\ApiResponse;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    use ApiResponse, RequiresDeletionConfirmation;

    /** @var list<string> */
    private const AUDIT_FIELDS = ['school_year_id', 'career_id', 'section', 'grade', 'shift', 'is_active'];

    public function index(Request $request): JsonResponse
    {
        $groups = Group::query()
            ->when($request->query('career_id'), fn ($query, $careerId) => $query->where('career_id', $careerId))
            ->when($request->query('school_year_id'), fn ($query, $schoolYearId) => $query->where('school_year_id', $schoolYearId))
            ->when($request->query('shift'), fn ($query, $shift) => $query->where('shift', $shift))
            ->when($request->has('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->get();

        return $this->successResponse('Grupos obtenidos correctamente.', AdminGroupResource::collection($groups));
    }

    public function show(int $group): JsonResponse
    {
        $model = Group::with('academicTutors.user')->find($group);

        if ($model === null) {
            throw ApiException::notFound('El grupo solicitado no existe.', 'GRP02');
        }

        return $this->successResponse('Grupo obtenido correctamente.', new AdminGroupResource($model));
    }

    public function store(StoreGroupRequest $request, AuditLogger $auditLogger): JsonResponse
    {
        $data = $request->validated();

        if (! SchoolYear::whereKey($data['school_year_id'])->exists()) {
            throw ApiException::notFound('El ciclo escolar indicado no existe.', 'SY01');
        }

        if (! Career::whereKey($data['career_id'])->exists()) {
            throw ApiException::notFound('La carrera solicitada no existe.', 'CAR01');
        }

        try {
            $group = Group::create($data);
        } catch (QueryException) {
            throw ApiException::conflict('Ya existe un grupo con ese grado y sección en la misma carrera y ciclo.', 'GRP03');
        }

        $auditLogger->log('group', $group->id, 'CREATE', $request->user()->id, null, $group->only(self::AUDIT_FIELDS));

        return $this->successResponse('Grupo creado correctamente.', new AdminGroupResource($group), 201);
    }

    public function update(UpdateGroupRequest $request, int $group, AuditLogger $auditLogger): JsonResponse
    {
        $model = Group::find($group);

        if ($model === null) {
            throw ApiException::notFound('El grupo solicitado no existe.', 'GRP02');
        }

        $before = $model->only(self::AUDIT_FIELDS);
        $data = $request->validated();

        if (isset($data['school_year_id']) && ! SchoolYear::whereKey($data['school_year_id'])->exists()) {
            throw ApiException::notFound('El ciclo escolar indicado no existe.', 'SY01');
        }

        if (isset($data['career_id']) && ! Career::whereKey($data['career_id'])->exists()) {
            throw ApiException::notFound('La carrera solicitada no existe.', 'CAR01');
        }

        try {
            $model->update($data);
        } catch (QueryException) {
            throw ApiException::conflict('Ya existe un grupo con ese grado y sección en la misma carrera y ciclo.', 'GRP03');
        }

        $auditLogger->log('group', $model->id, 'UPDATE', $request->user()->id, $before, $model->only(self::AUDIT_FIELDS));

        return $this->successResponse('Grupo actualizado correctamente.', new AdminGroupResource($model));
    }

    public function destroy(Request $request, int $group, AuditLogger $auditLogger): JsonResponse
    {
        $this->ensureConfirmed($request);

        $model = Group::find($group);

        if ($model === null) {
            throw ApiException::notFound('El grupo solicitado no existe.', 'GRP02');
        }

        if ($model->students()->active()->exists()) {
            throw ApiException::conflict('No se puede desactivar un grupo con alumnos activos asignados.', 'GRP04');
        }

        $before = $model->only(self::AUDIT_FIELDS);
        $model->update(['is_active' => false]);

        $auditLogger->log('group', $model->id, 'DELETE', $request->user()->id, $before, $model->only(self::AUDIT_FIELDS));

        return $this->successResponse('Grupo dado de baja correctamente.', new AdminGroupResource($model));
    }

    /**
     * Same shape as Director\GroupController::schedule — no career scoping since admin can
     * inspect any group's schedule.
     */
    public function schedule(int $group): JsonResponse
    {
        $model = Group::find($group);

        if ($model === null) {
            throw ApiException::notFound('El grupo solicitado no existe.', 'GRP02');
        }

        $schedules = $model->schedules()
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
}
