<?php

namespace App\Services\Administrador;

use App\Exceptions\ApiException;
use App\Models\Role;
use App\Models\User;
use App\Services\Administrador\Concerns\SendsWelcomePasswordEmail;
use App\Services\Governance\GovernanceClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Crea cuentas de director_carrera/administrador — a diferencia de alumno/profesor,
 * no tienen grupo, tutores ni NFC asociados al momento de crearse.
 */
class CreateStaffUserService
{
    use SendsWelcomePasswordEmail;

    public function __construct(protected GovernanceClient $governance) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, string $role, ?UploadedFile $photo): User
    {
        if (User::where('email', $data['email'])->exists()) {
            throw ApiException::conflict('Ya existe un usuario registrado con ese correo.', 'USR04');
        }

        $fullName = trim("{$data['first_name']} {$data['first_surname']} {$data['second_surname']}");

        try {
            $response = $this->governance->createUser([
                'name' => $fullName,
                'email' => $data['email'],
                'role' => $role,
                'active' => true,
            ]);
        } catch (ConnectionException) {
            throw new ApiException('No se pudo conectar con gobernanza. Inténtalo más tarde.', 503);
        }

        $photoPath = $photo?->store('users', 'public') ?? 'users/default.png';

        $user = User::create([
            ...$data,
            'role_id' => Role::where('name', $role)->firstOrFail()->id,
            'governance_user_id' => $response['data']['user']['id'],
            'password' => Hash::make(Str::random(32)),
            'active' => true,
            'photo' => $photoPath,
        ]);

        $temporaryPassword = $response['data']['temporary_password'] ?? null;
        $user->temporary_password = $temporaryPassword;

        if ($temporaryPassword !== null) {
            $this->sendWelcomeEmail($user, $temporaryPassword);
        }

        return $user;
    }
}
