<?php

namespace App\Http\Controllers\Administrador;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\RequiresDeletionConfirmation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administrador\StoreStudentRequest;
use App\Http\Requests\Administrador\StoreTutorRequest;
use App\Http\Requests\Administrador\UpdateStudentRequest;
use App\Http\Requests\Administrador\UpdateTutorRequest;
use App\Http\Requests\Concerns\ValidatesEvidenceFile;
use App\Http\Resources\AdminStudentResource;
use App\Http\Resources\JustificationResource;
use App\Models\Group;
use App\Models\Justification;
use App\Models\Tutor;
use App\Models\User;
use App\Services\Administrador\CreateStudentService;
use App\Services\AuditLogger;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentController extends Controller
{
    use ApiResponse, RequiresDeletionConfirmation, ValidatesEvidenceFile;

    /** @var list<string> */
    private const AUDIT_FIELDS = [
        'first_name', 'second_name', 'first_surname', 'second_surname',
        'email', 'phone', 'address', 'birth_date', 'gender', 'group_id', 'active', 'photo',
    ];

    public function index(Request $request): JsonResponse
    {
        $students = User::query()
            ->whereHas('role', fn ($query) => $query->where('name', 'alumno'))
            ->when($request->query('search'), fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('first_surname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->when($request->query('group_id'), fn ($query, $groupId) => $query->where('group_id', $groupId))
            ->when($request->query('career_id'), fn ($query, $careerId) => $query->whereHas('group', fn ($query) => $query->where('career_id', $careerId)))
            ->when($request->has('active'), fn ($query) => $query->where('active', $request->boolean('active')))
            ->with('tutors')
            ->get();

        return $this->successResponse('Alumnos obtenidos correctamente.', AdminStudentResource::collection($students));
    }

    public function show(int $student): JsonResponse
    {
        $model = $this->findStudent($student)->load('tutors');

        return $this->successResponse('Alumno obtenido correctamente.', new AdminStudentResource($model));
    }

    public function store(StoreStudentRequest $request, CreateStudentService $service, AuditLogger $auditLogger): JsonResponse
    {
        $data = $request->validated();

        if (! Group::whereKey($data['group_id'])->exists()) {
            throw ApiException::notFound('El grupo solicitado no existe.', 'GRP02');
        }

        $this->assertValidEvidence($request->file('photo'), 3, ['image/jpeg', 'image/png']);

        $studentData = collect($data)->only([
            'first_name', 'second_name', 'first_surname', 'second_surname',
            'email', 'phone', 'birth_date', 'gender', 'group_id',
        ])->all();

        $tutorData = isset($data['tutor_first_name']) ? [
            'first_name' => $data['tutor_first_name'],
            'first_surname' => $data['tutor_first_surname'],
            'second_surname' => $data['tutor_second_surname'] ?? '',
            'phone' => $data['tutor_phone'],
            'relationship' => $data['tutor_relationship'],
        ] : null;

        $student = $service->create($studentData, $tutorData, $request->file('photo'));

        $auditLogger->log('student', $student->id, 'CREATE', $request->user()->id, null, $student->only(self::AUDIT_FIELDS));

        return $this->successResponse('Alumno creado correctamente.', new AdminStudentResource($student->load('tutors')), 201);
    }

    public function update(UpdateStudentRequest $request, int $student, AuditLogger $auditLogger): JsonResponse
    {
        $model = $this->findStudent($student);
        $before = $model->only(self::AUDIT_FIELDS);

        $data = $request->validated();

        if (isset($data['email']) && User::where('email', $data['email'])->whereKeyNot($model->id)->exists()) {
            throw ApiException::conflict('Ya existe un usuario registrado con ese correo.', 'USR04');
        }

        $this->assertValidEvidence($request->file('photo'), 3, ['image/jpeg', 'image/png']);

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('users', 'public');
        }

        $model->update($data);

        $auditLogger->log('student', $model->id, 'UPDATE', $request->user()->id, $before, $model->only(self::AUDIT_FIELDS));

        return $this->successResponse('Alumno actualizado correctamente.', new AdminStudentResource($model));
    }

    public function destroy(Request $request, int $student, AuditLogger $auditLogger): JsonResponse
    {
        $this->ensureConfirmed($request);

        $model = $this->findStudent($student);
        $before = $model->only(self::AUDIT_FIELDS);
        $model->update(['active' => false]);

        $auditLogger->log('student', $model->id, 'DELETE', $request->user()->id, $before, $model->only(self::AUDIT_FIELDS));

        return $this->successResponse('Alumno dado de baja correctamente.', new AdminStudentResource($model));
    }

    public function addTutor(StoreTutorRequest $request, int $student): JsonResponse
    {
        $model = $this->findStudent($student);
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
            DB::table('student_tutor')->where('student_id', $model->id)->update(['is_primary' => false]);
        }

        $model->tutors()->attach($tutor->id, [
            'relationship' => $data['relationship'],
            'is_primary' => $data['is_primary'] ?? false,
            'receives_notifications' => $data['receives_notifications'] ?? true,
        ]);

        return $this->successResponse('Tutor agregado correctamente.', new AdminStudentResource($model->load('tutors')), 201);
    }

    public function updateTutor(UpdateTutorRequest $request, int $student, int $tutor): JsonResponse
    {
        $model = $this->findStudent($student);
        $tutorModel = $model->tutors()->where('tutors.id', $tutor)->first();

        if ($tutorModel === null) {
            throw ApiException::notFound('El tutor solicitado no existe o no está asignado a este alumno.', 'TUT01');
        }

        $data = $request->validated();

        $tutorFields = collect($data)->only(['first_name', 'second_name', 'first_surname', 'second_surname', 'phone'])->all();

        if ($tutorFields !== []) {
            $tutorModel->update($tutorFields);
        }

        if (($data['is_primary'] ?? false) === true) {
            DB::table('student_tutor')->where('student_id', $model->id)->update(['is_primary' => false]);
        }

        $pivotFields = collect($data)->only(['relationship', 'is_primary', 'receives_notifications'])->all();

        if ($pivotFields !== []) {
            $model->tutors()->updateExistingPivot($tutorModel->id, $pivotFields);
        }

        return $this->successResponse('Tutor actualizado correctamente.', new AdminStudentResource($model->load('tutors')));
    }

    public function removeTutor(int $student, int $tutor): JsonResponse
    {
        $model = $this->findStudent($student);
        $tutorModel = $model->tutors()->where('tutors.id', $tutor)->first();

        if ($tutorModel === null) {
            throw ApiException::notFound('El tutor solicitado no existe o no está asignado a este alumno.', 'TUT01');
        }

        if ($model->tutors()->count() === 1) {
            throw ApiException::conflict('No puedes eliminar al único tutor del alumno. Asigna otro primero.', 'TUT02');
        }

        $model->tutors()->detach($tutor);

        return $this->successResponse('Tutor eliminado correctamente.', new AdminStudentResource($model->load('tutors')));
    }

    /**
     * Same shape as Director\StudentController::justifications — no career scoping.
     */
    public function justifications(int $student): JsonResponse
    {
        $studentModel = $this->findStudent($student);

        $justifications = Justification::query()
            ->whereHas('attendance', fn ($query) => $query->where('student_id', $studentModel->id))
            ->with(['attendance.schedule.subject', 'attendance.schedule.teacher', 'reviewedBy'])
            ->latest()
            ->get();

        return $this->successResponse('Justificantes obtenidos correctamente.', JustificationResource::collection($justifications));
    }

    private function findStudent(int $id): User
    {
        $student = User::whereHas('role', fn ($query) => $query->where('name', 'alumno'))->find($id);

        if ($student === null) {
            throw ApiException::notFound('El usuario solicitado no existe.', 'USR01');
        }

        return $student;
    }
}
