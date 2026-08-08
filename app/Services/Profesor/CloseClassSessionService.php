<?php

namespace App\Services\Profesor;

use App\Events\AttendanceRegistered;
use App\Exceptions\ApiException;
use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\User;
use App\Services\NotificationService;

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
        $registeredIds = Attendance::where('class_session_id', $session->id)->pluck('student_id');

        foreach ($students->whereNotIn('id', $registeredIds) as $student) {
            $attendance = Attendance::create([
                'class_session_id' => $session->id,
                'student_id' => $student->id,
                'schedule_id' => $session->schedule_id,
                'devices_id' => $session->device_id,
                'registered_at' => now(),
                'status' => 'FALTA',
                'method' => 'SISTEMA',
            ]);

            AttendanceRegistered::dispatch($attendance, $performedByUserId);

            $this->notifications->notifyAbsence($attendance);
        }

        $session->update([
            'status' => 'CERRADA',
            'closed_at' => now(),
            'is_active' => false,
        ]);

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
