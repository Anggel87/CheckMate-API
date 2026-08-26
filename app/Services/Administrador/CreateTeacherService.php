<?php

namespace App\Services\Administrador;

use App\Exceptions\ApiException;
use App\Models\AcademicTutor;
use App\Models\Role;
use App\Models\User;
use App\Services\Administrador\Concerns\SendsWelcomePasswordEmail;
use App\Services\Governance\GovernanceClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateTeacherService
{
    use SendsWelcomePasswordEmail;

    public function __construct(protected GovernanceClient $governance) {}

    /**
     * @param  array<string, mixed>  $teacherData
     */
    public function create(array $teacherData, ?UploadedFile $photo, bool $isAcademicTutor = false): User
    {
        if (User::where('email', $teacherData['email'])->exists()) {
            throw ApiException::conflict('Ya existe un usuario registrado con ese correo.', 'USR04');
        }

        $fullName = trim("{$teacherData['first_name']} {$teacherData['first_surname']} {$teacherData['second_surname']}");
        $roleName = $isAcademicTutor ? 'tutor_academico' : 'profesor';

        try {
            $response = $this->governance->createUser([
                'name' => $fullName,
                'email' => $teacherData['email'],
                'role' => $roleName,
                'active' => true,
            ]);
        } catch (ConnectionException) {
            throw new ApiException('No se pudo conectar con gobernanza. Inténtalo más tarde.', 503);
        }

        $photoPath = $photo?->store('users', 'public') ?? 'users/default.png';

        $teacher = User::create([
            ...$teacherData,
            'role_id' => Role::where('name', $roleName)->firstOrFail()->id,
            'governance_user_id' => $response['data']['user']['id'],
            'password' => Hash::make(Str::random(32)),
            'active' => true,
            'photo' => $photoPath,
        ]);

        if ($isAcademicTutor) {
            AcademicTutor::create([
                'user_id' => $teacher->id,
                'is_active' => true,
            ]);
        }

        $temporaryPassword = $response['data']['temporary_password'] ?? null;
        $teacher->temporary_password = $temporaryPassword;

        if ($temporaryPassword !== null) {
            $this->sendWelcomeEmail($teacher, $temporaryPassword);
        }

        return $teacher;
    }
}
