<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Governance\GovernanceClient;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class LinkSeededUsersToGovernance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'governance:link-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea en gobernanza los usuarios de prueba del SimpleUserSeeder (más unos alumnos extra) que aún no tienen governance_user_id, y guarda el enlace.';

    /**
     * Cuántos alumnos sembrados adicionales (fuera del SimpleUserSeeder) vincular,
     * para tener con qué probar el flujo de alumno/tutor sin vincular la base completa.
     */
    private const EXTRA_STUDENTS = 3;

    /**
     * Mapea el nombre de rol local (tabla `roles`) al nombre de rol que espera gobernanza.
     *
     * @var array<string, string>
     */
    private const GOVERNANCE_ROLES = [
        'alumno' => 'alumno',
        'profesor' => 'profesor',
        'tutor_academico' => 'tutor_academico',
        'administrador' => 'administrator',
        'director_carrera' => 'career_director',
    ];

    public function handle(GovernanceClient $governance): int
    {
        $demoUsers = User::with('role')
            ->whereNull('governance_user_id')
            ->where('email', 'like', '%@checkmate.com')
            ->get();

        $extraStudents = User::with('role')
            ->whereNull('governance_user_id')
            ->where('email', 'not like', '%@checkmate.com')
            ->whereHas('role', fn ($query) => $query->where('name', 'alumno'))
            ->take(self::EXTRA_STUDENTS)
            ->get();

        $users = $demoUsers->merge($extraStudents);

        if ($users->isEmpty()) {
            $this->components->info('No hay usuarios sembrados pendientes de vincular con gobernanza.');

            return self::SUCCESS;
        }

        $rows = [];

        foreach ($users as $user) {
            $governanceRole = self::GOVERNANCE_ROLES[$user->role->name] ?? null;

            if ($governanceRole === null) {
                $this->components->warn("Se omitió {$user->email}: el rol local '{$user->role->name}' no tiene equivalente en gobernanza.");

                continue;
            }

            try {
                $response = $governance->createUser([
                    'name' => $user->fullName(),
                    'email' => $user->email,
                    'role' => $governanceRole,
                    'active' => true,
                ]);
            } catch (ConnectionException $e) {
                $this->components->error('No se pudo conectar con gobernanza. ¿Está corriendo? '.$e->getMessage());

                return self::FAILURE;
            } catch (RequestException $e) {
                $this->components->warn("Se omitió {$user->email}: gobernanza respondió {$e->response->status()}.");

                continue;
            }

            $user->update(['governance_user_id' => $response['data']['user']['id']]);

            $rows[] = [
                $user->email,
                $user->role->name,
                $response['data']['temporary_password'] ?? '(sin cambios, ya existía)',
            ];
        }

        if ($rows !== []) {
            $this->components->info('Usuarios vinculados con gobernanza:');
            $this->table(['Email', 'Rol', 'Password temporal'], $rows);
        }

        return self::SUCCESS;
    }
}
