<?php

namespace App\Console\Commands;

use App\Models\Classroom;
use App\Models\Device;
use Illuminate\Console\Command;

class RegisterDevice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'device:register {mac_address} {classroom_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Da de alta (o actualiza) un dispositivo ESP32 y lo asigna a un salón, sin pasar por el CRUD de administrador.';

    public function handle(): int
    {
        $macAddress = strtoupper((string) $this->argument('mac_address'));
        $classroomId = (int) $this->argument('classroom_id');

        if (! preg_match('/^([0-9A-F]{2}:){5}[0-9A-F]{2}$/', $macAddress)) {
            $this->components->error('La dirección MAC no tiene un formato válido (ej. AA:BB:CC:DD:EE:FF).');

            return self::FAILURE;
        }

        if (! Classroom::whereKey($classroomId)->exists()) {
            $this->components->error("No existe el salón con id {$classroomId}.");

            return self::FAILURE;
        }

        $device = Device::updateOrCreate(
            ['mac_address' => $macAddress],
            ['classroom_id' => $classroomId, 'is_active' => true],
        );

        $this->components->info("Dispositivo {$device->mac_address} registrado para el salón #{$classroomId}.");

        return self::SUCCESS;
    }
}
