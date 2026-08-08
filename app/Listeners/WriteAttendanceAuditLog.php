<?php

namespace App\Listeners;

use App\Events\AttendanceRegistered;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Log;
use Throwable;

class WriteAttendanceAuditLog
{
    public function __construct(protected AuditLogger $auditLogger) {}

    /**
     * La auditoría es un efecto secundario, no la fuente de verdad — si falla, el
     * registro de asistencia ya quedó guardado (mismo criterio que el envío de
     * WhatsApp en NotificationService::sendWhatsApp()).
     */
    public function handle(AttendanceRegistered $event): void
    {
        try {
            $this->auditLogger->log(
                'attendance',
                $event->attendance->id,
                'CREATE',
                $event->performedByUserId,
                null,
                $event->attendance->only(['student_id', 'schedule_id', 'devices_id', 'status', 'method', 'registered_at']),
            );
        } catch (Throwable $e) {
            Log::warning('No se pudo escribir el log de auditoría de la asistencia.', [
                'attendance_id' => $event->attendance->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
