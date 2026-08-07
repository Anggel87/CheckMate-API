<?php

namespace App\Http\Controllers\Administrador;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\RequiresDeletionConfirmation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administrador\StoreTeacherRequest;
use App\Http\Requests\Administrador\UpdateAcademicTutorRequest;
use App\Http\Requests\Administrador\UpdateTeacherRequest;
use App\Http\Requests\Concerns\ValidatesEvidenceFile;
use App\Http\Resources\AdminTeacherResource;
use App\Models\AcademicTutor;
use App\Models\Group;
use App\Models\Role;
use App\Models\User;
use App\Services\Administrador\CreateTeacherService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    use ApiResponse, RequiresDeletionConfirmation, ValidatesEvidenceFile;

    public function index(Request $request): JsonResponse
    {
        $teachers = User::query()
            ->whereHas('role', fn ($query) => $query->whereIn('name', ['profesor', 'tutor_academico']))
            ->with(['role', 'academicTutor'])
            ->withCount('schedules')
            ->when($request->query('search'), fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('first_surname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->when($request->has('is_academic_tutor'), fn ($query) => $query->whereHas(
                'role',
                fn ($query) => $query->where('name', $request->boolean('is_academic_tutor') ? 'tutor_academico' : 'profesor')
            ))
            ->when($request->has('active'), fn ($query) => $query->where('active', $request->boolean('active')))
            ->get();

        return $this->successResponse('Profesores obtenidos correctamente.', AdminTeacherResource::collection($teachers));
    }

    public function show(int $teacher): JsonResponse
    {
        $model = $this->findTeacher($teacher)
            ->load(['role', 'academicTutor.activeGroups'])
            ->loadCount('schedules');

        return $this->successResponse('Profesor obtenido correctamente.', new AdminTeacherResource($model));
    }

    public function store(StoreTeacherRequest $request, CreateTeacherService $service): JsonResponse
    {
        $data = $request->validated();

        $this->assertValidEvidence($request->file('photo'), 3, ['image/jpeg', 'image/png']);

        $isAcademicTutor = $data['is_academic_tutor'] ?? false;

        $teacherData = collect($data)->only([
            'first_name', 'second_name', 'first_surname', 'second_surname',
            'email', 'phone', 'birth_date', 'gender',
        ])->all();

        $teacher = $service->create($teacherData, $request->file('photo'), $isAcademicTutor);

        return $this->successResponse('Profesor creado correctamente.', new AdminTeacherResource($teacher->load('role')), 201);
    }

    public function update(UpdateTeacherRequest $request, int $teacher): JsonResponse
    {
        $model = $this->findTeacher($teacher);

        $data = $request->validated();

        $this->assertValidEvidence($request->file('photo'), 3, ['image/jpeg', 'image/png']);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('users', 'public');
        }

        $model->update($data);

        return $this->successResponse('Profesor actualizado correctamente.', new AdminTeacherResource($model->load('role')));
    }

    public function destroy(Request $request, int $teacher): JsonResponse
    {
        $this->ensureConfirmed($request);

        $model = $this->findTeacher($teacher);

        if ($model->schedules()->where('is_active', true)->exists()) {
            throw ApiException::conflict('No se puede desactivar a un profesor con horarios activos asignados.', 'USR05');
        }

        $model->update(['active' => false]);

        return $this->successResponse('Profesor dado de baja correctamente.', new AdminTeacherResource($model->load('role')));
    }

    public function toggleAcademicTutor(UpdateAcademicTutorRequest $request, int $teacher): JsonResponse
    {
        $model = $this->findTeacher($teacher);
        $data = $request->validated();

        $groupIds = $data['group_ids'] ?? null;

        if ($groupIds !== null && Group::whereIn('id', $groupIds)->count() !== count($groupIds)) {
            throw ApiException::notFound('El grupo solicitado no existe.', 'GRP02');
        }

        $academicTutor = AcademicTutor::updateOrCreate(
            ['user_id' => $model->id],
            ['is_active' => $data['is_active']]
        );

        $model->update([
            'role_id' => Role::where('name', $data['is_active'] ? 'tutor_academico' : 'profesor')->firstOrFail()->id,
        ]);

        if ($groupIds !== null) {
            $academicTutor->groups()->sync(
                collect($groupIds)->mapWithKeys(fn ($id) => [$id => ['is_active' => true, 'assigned_at' => now()]])
            );
        }

        $groups = $academicTutor->activeGroups()->get();

        return $this->successResponse('Tutor académico actualizado correctamente.', [
            'teacher_id' => $model->id,
            'is_academic_tutor' => $data['is_active'],
            'groups' => $groups->map(fn (Group $group) => [
                'id' => $group->id,
                'grade' => $group->grade,
                'section' => $group->section,
            ])->values(),
        ]);
    }

    private function findTeacher(int $id): User
    {
        $teacher = User::whereHas('role', fn ($query) => $query->whereIn('name', ['profesor', 'tutor_academico']))->find($id);

        if ($teacher === null) {
            throw ApiException::notFound('El usuario solicitado no existe.', 'USR01');
        }

        return $teacher;
    }
}
