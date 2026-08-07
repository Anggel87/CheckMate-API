<?php

namespace App\Services\Administrador;

use App\Exceptions\ApiException;
use App\Models\Role;
use App\Models\Tutor;
use App\Models\User;
use App\Services\Administrador\Concerns\SendsWelcomePasswordEmail;
use App\Services\Governance\GovernanceClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateStudentService
{
    use SendsWelcomePasswordEmail;

    public function __construct(protected GovernanceClient $governance) {}

    /**
     * @param  array<string, mixed>  $studentData
     * @param  array<string, mixed>  $tutorData
     */
    public function create(array $studentData, array $tutorData, ?UploadedFile $photo): User
    {
        if (User::where('email', $studentData['email'])->exists()) {
            throw ApiException::conflict('Ya existe un usuario registrado con ese correo.', 'USR04');
        }

        $fullName = trim("{$studentData['first_name']} {$studentData['first_surname']} {$studentData['second_surname']}");

        try {
            $response = $this->governance->createUser([
                'name' => $fullName,
                'email' => $studentData['email'],
                'role' => 'alumno',
                'active' => true,
            ]);
        } catch (ConnectionException) {
            throw new ApiException('No se pudo conectar con gobernanza. Inténtalo más tarde.', 503);
        }

        $photoPath = $photo?->store('users', 'public') ?? 'users/default.png';

        $student = User::create([
            ...$studentData,
            'role_id' => Role::where('name', 'alumno')->firstOrFail()->id,
            'governance_user_id' => $response['data']['user']['id'],
            'password' => Hash::make(Str::random(32)),
            'active' => true,
            'photo' => $photoPath,
        ]);

        $tutor = Tutor::create([
            'first_name' => $tutorData['tutor_first_name'],
            'first_surname' => $tutorData['tutor_first_surname'],
            'second_surname' => $tutorData['tutor_second_surname'] ?? '',
            'phone' => $tutorData['tutor_phone'],
            'is_active' => true,
        ]);

        $student->tutors()->attach($tutor->id, [
            'relationship' => $tutorData['tutor_relationship'],
            'is_primary' => true,
            'receives_notifications' => true,
        ]);

        $temporaryPassword = $response['data']['temporary_password'] ?? null;
        $student->temporary_password = $temporaryPassword;

        if ($temporaryPassword !== null) {
            $this->sendWelcomeEmail($student, $temporaryPassword);
        }

        return $student;
    }
}
