<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Governance\GovernanceClient;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

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

        $baseUrl = config('services.governance.base_url');
        $clientId = config('services.governance.client_id');

        $this->components->info("Vinculando {$users->count()} usuario(s) contra {$baseUrl} (client_id: {$clientId}).");
        Log::info('governance:link-users iniciado', [
            'base_url' => $baseUrl,
            'client_id' => $clientId,
            'users_count' => $users->count(),
        ]);

        $rows = [];

        foreach ($users as $user) {
            try {
                $response = $governance->createUser([
                    'name' => $user->fullName(),
                    'email' => $user->email,
                    'role' => $user->role->name,
                    'active' => true,
                ]);
            } catch (ConnectionException $e) {
                $this->components->error('No se pudo conectar con gobernanza. ¿Está corriendo? '.$e->getMessage());
                Log::error('governance:link-users no pudo conectar con gobernanza.', [
                    'base_url' => $baseUrl,
                    'exception' => $e->getMessage(),
                ]);

                return self::FAILURE;
            } catch (RequestException $e) {
                $status = $e->response->status();
                $body = $e->response->json() ?? $e->response->body();

                $this->components->warn("Se omitió {$user->email}: gobernanza respondió {$status}.");
                $this->line('  '.json_encode($body));

                Log::warning('governance:link-users omitió un usuario.', [
                    'base_url' => $baseUrl,
                    'client_id' => $clientId,
                    'email' => $user->email,
                    'status' => $status,
                    'response_body' => $body,
                ]);

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
