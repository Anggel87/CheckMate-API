<?php

namespace App\Services\Profesor;

use App\Exceptions\ApiException;
use App\Models\ClassSession;
use App\Models\Device;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Database\QueryException;

class OpenClassSessionService
{
    public function open(User $teacher, int $scheduleId, string $date): ClassSession
    {
        $schedule = Schedule::find($scheduleId);

        if ($schedule === null || $schedule->teacher_id !== $teacher->id) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }

        if (ClassSession::where('schedule_id', $schedule->id)->whereDate('date', $date)->exists()) {
            throw ApiException::conflict('Ya existe una sesión abierta para esta clase en la fecha indicada.', 'SES01');
        }

        $device = Device::where('classroom_id', $schedule->classroom_id)->where('is_active', true)->first();

        if ($device === null) {
            throw ApiException::notFound('El dispositivo solicitado no existe.', 'DEV01');
        }

        try {
            $session = ClassSession::create([
                'schedule_id' => $schedule->id,
                'date' => $date,
                'teacher_id' => $teacher->id,
                'device_id' => $device->id,
                'opened_at' => now(),
                'status' => 'ABIERTA',
                'opening_method' => 'MANUAL',
                'is_active' => true,
            ]);
        } catch (QueryException) {
            throw ApiException::conflict('Ya existe una sesión abierta para esta clase en la fecha indicada.', 'SES01');
        }

        return $session;
    }
}
