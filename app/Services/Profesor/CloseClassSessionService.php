<?php

namespace App\Services\Profesor;

use App\Events\AttendanceRegistered;
use App\Exceptions\ApiException;
use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;

class CloseClassSessionService
{
    public function __construct(protected NotificationService $notifications) {}

    /**
     * @return array<string, mixed>
     */
    public function close(User $teacher, int $sessionId): array
    {
        $session = ClassSession::with('schedule.group')->find($sessionId);

        if ($session === null || $session->teacher_id !== $teacher->id) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }

        if ($session->status !== 'ABIERTA') {
            throw ApiException::conflict('La sesión ya fue cerrada anteriormente.', 'SES03');
        }

        return $this->closeSession($session, $teacher->id);
    }

    /**
     * @return array<string, mixed>
     */
    public function closeSession(ClassSession $session, ?int $performedByUserId = null): array
    {
        $session->loadMissing('schedule.group');

        $students = $session->schedule->group->students()->active()->get();
        $sessionId = $session->id;

        // Bloquea la fila de la sesión para que este cierre y un tap NFC concurrente no se
        // entrelacen: sin esto, un alumno que tapea justo cuando el cron cierra la clase puede
        // terminar con dos registros (uno NFC y uno FALTA por SISTEMA) para la misma sesión. El
        // lock también hace este método seguro de llamar dos veces a la vez (p. ej. el cron y un
        // cierre manual del profesor casi simultáneos): quien llegue segundo ve la sesión ya
        // CERRADA y no repite el trabajo.
        $newAbsences = DB::transaction(function () use ($sessionId, $students, &$session) {
            $session = ClassSession::whereKey($sessionId)->lockForUpdate()->firstOrFail();

            if ($session->status !== 'ABIERTA') {
                return [];
            }

            $registeredIds = Attendance::where('class_session_id', $sessionId)->pluck('student_id');

            $created = $students->whereNotIn('id', $registeredIds)->map(fn ($student) => Attendance::create([
                'class_session_id' => $sessionId,
                'student_id' => $student->id,
                'schedule_id' => $session->schedule_id,
                'devices_id' => $session->device_id,
                'registered_at' => now(),
                'status' => 'FALTA',
                'method' => 'SISTEMA',
            ]));

            $session->update([
                'status' => 'CERRADA',
                'closed_at' => now(),
                'is_active' => false,
            ]);

            return $created->all();
        });

        // Fuera de la transacción a propósito: notifyAbsence() manda un WhatsApp real (I/O
        // externo) y no debe mantener el lock de la fila de sesión abierto mientras espera esa
        // llamada de red.
        foreach ($newAbsences as $attendance) {
            AttendanceRegistered::dispatch($attendance, $performedByUserId);

            $this->notifications->notifyAbsence($attendance);
        }

        $counts = Attendance::where('class_session_id', $session->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return [
            'session_id' => $session->id,
            'status' => $session->status,
            'total_students' => $students->count(),
            'on_time' => (int) ($counts['PRESENTE'] ?? 0),
            'late' => (int) ($counts['RETARDO'] ?? 0),
            'absent' => (int) ($counts['FALTA'] ?? 0),
            'closed_at' => $session->closed_at->toIso8601String(),
        ];
    }
}
