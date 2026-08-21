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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateStudentService
{
    use SendsWelcomePasswordEmail;

    public function __construct(protected GovernanceClient $governance) {}

    /**
     * @param  array<string, mixed>  $studentData
     * @param  array<string, mixed>|null  $tutorData  Unprefixed shape (first_name, first_surname,
     *                                                 second_surname, phone, relationship) — un
     *                                                 alumno puede crearse sin tutor todavia.
     */
    public function create(array $studentData, ?array $tutorData, ?UploadedFile $photo): User
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

        if ($tutorData !== null) {
            $this->attachTutor($student, $tutorData, isPrimary: true);
        }

        $temporaryPassword = $response['data']['temporary_password'] ?? null;
        $student->temporary_password = $temporaryPassword;

        if ($temporaryPassword !== null) {
            $this->sendWelcomeEmail($student, $temporaryPassword);
        }

        return $student;
    }

    /**
     * @param  array<string, mixed>  $tutorData
     */
    public function attachTutor(User $student, array $tutorData, bool $isPrimary = false): Tutor
    {
        $tutor = Tutor::create([
            'first_name' => $tutorData['first_name'],
            'second_name' => $tutorData['second_name'] ?? null,
            'first_surname' => $tutorData['first_surname'],
            'second_surname' => $tutorData['second_surname'] ?? '',
            'phone' => $tutorData['phone'],
            'is_active' => true,
        ]);

        if ($isPrimary) {
            DB::table('student_tutor')->where('student_id', $student->id)->update(['is_primary' => false]);
        }

        $student->tutors()->attach($tutor->id, [
            'relationship' => $tutorData['relationship'],
            'is_primary' => $isPrimary,
            'receives_notifications' => $tutorData['receives_notifications'] ?? true,
        ]);

        return $tutor;
    }
}
