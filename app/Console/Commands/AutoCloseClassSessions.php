<?php

namespace App\Console\Commands;

use App\Models\ClassSession;
use App\Services\Profesor\CloseClassSessionService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class AutoCloseClassSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'class-sessions:auto-close';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cierra automáticamente las sesiones de clase que ya pasaron su hora de fin (marca FALTA a quien no registró).';

    public function handle(CloseClassSessionService $service): int
    {
        $now = Carbon::now(config('app.timezone'));

        $sessions = ClassSession::with('schedule')
            ->where('status', 'ABIERTA')
            ->whereDate('date', '<=', $now->toDateString())
            ->get()
            ->filter(fn (ClassSession $session) => Carbon::parse(
                $session->date->format('Y-m-d').' '.$session->schedule->end_time
            )->lt($now));

        if ($sessions->isEmpty()) {
            $this->components->info('No hay sesiones de clase pendientes de cerrar.');

            return self::SUCCESS;
        }

        foreach ($sessions as $session) {
            $service->closeSession($session);
        }

        $this->components->info("Se cerraron {$sessions->count()} sesión(es) de clase.");

        return self::SUCCESS;
    }
}
