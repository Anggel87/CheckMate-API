<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Governance\GovernanceClient;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

class LinkSeededStudentsToGovernance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'governance:link-students';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crea en gobernanza los alumnos sembrados localmente que aún no tienen governance_user_id, y guarda el enlace.';

    public function handle(GovernanceClient $governance): int
    {
        $students = User::whereHas('role', fn ($query) => $query->where('name', 'alumno'))
            ->whereNull('governance_user_id')
            ->get();

        if ($students->isEmpty()) {
            $this->components->info('No hay alumnos sembrados pendientes de vincular con gobernanza.');

            return self::SUCCESS;
        }

        $rows = [];

        foreach ($students as $student) {
            try {
                $response = $governance->createUser([
                    'name' => $student->fullName(),
                    'email' => $student->email,
                    'role' => 'alumno',
                    'active' => true,
                ]);
            } catch (ConnectionException $e) {
                $this->components->error('No se pudo conectar con gobernanza. ¿Está corriendo? '.$e->getMessage());

                return self::FAILURE;
            } catch (RequestException $e) {
                $this->components->warn("Se omitió {$student->email}: gobernanza respondió {$e->response->status()}.");

                continue;
            }

            $student->update(['governance_user_id' => $response['data']['user']['id']]);

            $rows[] = [
                $student->email,
                $response['data']['temporary_password'] ?? '(sin cambios, ya existía)',
            ];
        }

        if ($rows !== []) {
            $this->components->info('Alumnos vinculados con gobernanza:');
            $this->table(['Email', 'Password temporal'], $rows);
        }

        return self::SUCCESS;
    }
}
