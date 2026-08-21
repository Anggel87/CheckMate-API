<?php

namespace App\Http\Controllers\Administrador;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\AssignsNfcUid;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administrador\StoreUserRequest;
use App\Http\Requests\Concerns\ValidatesEvidenceFile;
use App\Http\Resources\AdminStaffUserResource;
use App\Http\Resources\AdminStudentResource;
use App\Http\Resources\AdminTeacherResource;
use App\Models\Group;
use App\Models\User;
use App\Services\Administrador\CreateStaffUserService;
use App\Services\Administrador\CreateStudentService;
use App\Services\Administrador\CreateTeacherService;
use App\Services\AuditLogger;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

/**
 * Punto de entrada unico para crear cualquier tipo de usuario ("Nuevo usuario"),
 * dejando que el rol elegido determine sus permisos (ya seedeados por rol, ver
 * PermissionSeeder) en vez de configurar permisos por usuario. Delega en los mismos
 * servicios ya usados por Student/TeacherController para alumno/profesor/tutor_academico
 * — solo director_carrera/administrador son nuevos aqui, ya que antes no tenian ningun
 * flujo de creacion desde el panel admin.
 */
class UserController extends Controller
{
    use ApiResponse, AssignsNfcUid, ValidatesEvidenceFile;

    private const ROLES_WITH_NFC = ['alumno', 'profesor', 'tutor_academico'];

    public function store(
        StoreUserRequest $request,
        CreateStudentService $studentService,
        CreateTeacherService $teacherService,
        CreateStaffUserService $staffService,
        AuditLogger $auditLogger,
    ): JsonResponse {
        $data = $request->validated();
        $role = $data['role'];

        $this->assertValidEvidence($request->file('photo'), 3, ['image/jpeg', 'image/png']);

        $commonData = collect($data)->only([
            'first_name', 'second_name', 'first_surname', 'second_surname',
            'email', 'phone', 'birth_date', 'gender',
        ])->all();

        $user = match ($role) {
            'alumno' => $this->createStudent($data, $commonData, $request->file('photo'), $studentService),
            'profesor', 'tutor_academico' => $teacherService->create($commonData, $request->file('photo'), $role === 'tutor_academico'),
            default => $staffService->create($commonData, $role, $request->file('photo')),
        };

        if (in_array($role, self::ROLES_WITH_NFC, true) && ! empty($data['nfc_uid'])) {
            $this->assignNfcUid($user, $data['nfc_uid']);
        }

        $auditLogger->log($this->auditEntityFor($role), $user->id, 'CREATE', $request->user()->id, null, [
            'role' => $role,
            'email' => $user->email,
        ]);

        return $this->successResponse('Usuario creado correctamente.', $this->resourceFor($role, $user), 201);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $commonData
     */
    private function createStudent(array $data, array $commonData, ?UploadedFile $photo, CreateStudentService $service): User
    {
        if (! Group::whereKey($data['group_id'])->exists()) {
            throw ApiException::notFound('El grupo solicitado no existe.', 'GRP02');
        }

        $tutors = $data['tutors'] ?? [];
        $firstTutor = array_shift($tutors);

        $student = $service->create([...$commonData, 'group_id' => $data['group_id']], $firstTutor, $photo);

        foreach ($tutors as $tutorData) {
            $service->attachTutor($student, $tutorData);
        }

        return $student;
    }

    private function auditEntityFor(string $role): string
    {
        return match ($role) {
            'alumno' => 'student',
            'profesor', 'tutor_academico' => 'teacher',
            default => 'user',
        };
    }

    private function resourceFor(string $role, User $user): AdminStudentResource|AdminTeacherResource|AdminStaffUserResource
    {
        return match ($role) {
            'alumno' => new AdminStudentResource($user->load('tutors')),
            'profesor', 'tutor_academico' => new AdminTeacherResource($user->load('role')),
            default => new AdminStaffUserResource($user->load('role')),
        };
    }
}
