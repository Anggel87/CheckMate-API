<?php

namespace App\Http\Controllers\Administrador;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\RequiresDeletionConfirmation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administrador\StoreScheduleRequest;
use App\Http\Requests\Administrador\UpdateScheduleRequest;
use App\Http\Resources\AdminScheduleResource;
use App\Models\Classroom;
use App\Models\Group;
use App\Models\Schedule;
use App\Models\SchoolYear;
use App\Models\Subject;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Only place in the API that can create/edit/deactivate a Schedule row — every other module
 * (NFC taps, class sessions, attendance, incidents) reads schedules but none of them can
 * create one, so this had to exist somewhere for the school to actually be usable.
 */
class ScheduleController extends Controller
{
    use ApiResponse, RequiresDeletionConfirmation;

    private const RELATIONS = ['group', 'subject', 'teacher', 'classroom'];

    public function index(Request $request): JsonResponse
    {
        $schedules = Schedule::query()
            ->with(self::RELATIONS)
            ->when($request->query('group_id'), fn ($query, $groupId) => $query->where('group_id', $groupId))
            ->when($request->query('teacher_id'), fn ($query, $teacherId) => $query->where('teacher_id', $teacherId))
            ->when($request->query('subject_id'), fn ($query, $subjectId) => $query->where('subject_id', $subjectId))
            ->when($request->query('classroom_id'), fn ($query, $classroomId) => $query->where('classroom_id', $classroomId))
            ->when($request->query('school_year_id'), fn ($query, $schoolYearId) => $query->where('school_year_id', $schoolYearId))
            ->when($request->query('day_of_week'), fn ($query, $day) => $query->where('day_of_week', $day))
            ->when($request->has('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();

        return $this->successResponse('Horarios obtenidos correctamente.', AdminScheduleResource::collection($schedules));
    }

    public function show(int $schedule): JsonResponse
    {
        $model = $this->findSchedule($schedule)->load(self::RELATIONS);

        return $this->successResponse('Horario obtenido correctamente.', new AdminScheduleResource($model));
    }

    public function store(StoreScheduleRequest $request): JsonResponse
    {
        $data = $request->validated();

        $this->assertReferencesExist($data);
        $this->assertNoConflicts($data);

        try {
            $schedule = Schedule::create([...$data, 'is_active' => true]);
        } catch (QueryException) {
            throw ApiException::conflict('Ya existe ese horario exacto para el grupo, materia y profesor indicados.', 'SCH02');
        }

        return $this->successResponse('Horario creado correctamente.', new AdminScheduleResource($schedule->load(self::RELATIONS)), 201);
    }

    public function update(UpdateScheduleRequest $request, int $schedule): JsonResponse
    {
        $model = $this->findSchedule($schedule);
        $data = $request->validated();

        $merged = [
            'school_year_id' => $data['school_year_id'] ?? $model->school_year_id,
            'group_id' => $data['group_id'] ?? $model->group_id,
            'subject_id' => $data['subject_id'] ?? $model->subject_id,
            'teacher_id' => $data['teacher_id'] ?? $model->teacher_id,
            'classroom_id' => $data['classroom_id'] ?? $model->classroom_id,
            'day_of_week' => $data['day_of_week'] ?? $model->day_of_week,
            'start_time' => $data['start_time'] ?? substr((string) $model->start_time, 0, 5),
            'end_time' => $data['end_time'] ?? substr((string) $model->end_time, 0, 5),
        ];

        if ($merged['end_time'] <= $merged['start_time']) {
            throw ApiException::unprocessable(
                'La hora de fin debe ser posterior a la hora de inicio.',
                'VAL01',
                ['end_time' => ['La hora de fin debe ser posterior a la hora de inicio.']]
            );
        }

        $this->assertReferencesExist($merged);
        $this->assertNoConflicts($merged, excludeId: $model->id);

        try {
            $model->update($data);
        } catch (QueryException) {
            throw ApiException::conflict('Ya existe ese horario exacto para el grupo, materia y profesor indicados.', 'SCH02');
        }

        return $this->successResponse('Horario actualizado correctamente.', new AdminScheduleResource($model->load(self::RELATIONS)));
    }

    public function destroy(Request $request, int $schedule): JsonResponse
    {
        $this->ensureConfirmed($request);

        $model = $this->findSchedule($schedule);
        $model->update(['is_active' => false]);

        return $this->successResponse('Horario dado de baja correctamente.', new AdminScheduleResource($model->load(self::RELATIONS)));
    }

    private function findSchedule(int $id): Schedule
    {
        $schedule = Schedule::find($id);

        if ($schedule === null) {
            throw ApiException::notFound('El horario solicitado no existe.', 'SCH01');
        }

        return $schedule;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertReferencesExist(array $data): void
    {
        if (! SchoolYear::whereKey($data['school_year_id'])->exists()) {
            throw ApiException::notFound('El ciclo escolar indicado no existe.', 'SY01');
        }

        if (! Group::whereKey($data['group_id'])->exists()) {
            throw ApiException::notFound('El grupo solicitado no existe.', 'GRP02');
        }

        if (! Subject::whereKey($data['subject_id'])->exists()) {
            throw ApiException::notFound('La materia solicitada no existe.', 'SUBJ01');
        }

        if (! Classroom::whereKey($data['classroom_id'])->exists()) {
            throw ApiException::notFound('El salón indicado no existe.', 'CLS01');
        }

        $teacher = User::whereHas('role', fn ($query) => $query->whereIn('name', ['profesor', 'tutor_academico']))
            ->find($data['teacher_id']);

        if ($teacher === null) {
            throw ApiException::notFound('El profesor indicado no existe o no tiene el rol correcto.', 'USR06');
        }
    }

    /**
     * A teacher, a classroom, or a group can't be in two overlapping classes at once.
     *
     * @param  array<string, mixed>  $data
     */
    private function assertNoConflicts(array $data, ?int $excludeId = null): void
    {
        $overlap = function (Builder $query) use ($data, $excludeId): Builder {
            return $query
                ->where('school_year_id', $data['school_year_id'])
                ->where('day_of_week', $data['day_of_week'])
                ->where('is_active', true)
                ->where('start_time', '<', $data['end_time'])
                ->where('end_time', '>', $data['start_time'])
                ->when($excludeId, fn ($query, $id) => $query->whereKeyNot($id));
        };

        if ($overlap(Schedule::where('teacher_id', $data['teacher_id']))->exists()) {
            throw ApiException::conflict('El profesor ya tiene otra clase asignada en ese horario.', 'SCH02');
        }

        if ($overlap(Schedule::where('classroom_id', $data['classroom_id']))->exists()) {
            throw ApiException::conflict('El salón ya está ocupado en ese horario.', 'SCH02');
        }

        if ($overlap(Schedule::where('group_id', $data['group_id']))->exists()) {
            throw ApiException::conflict('El grupo ya tiene otra clase asignada en ese horario.', 'SCH02');
        }
    }
}
