<?php

namespace App\Services\Profesor;

use App\Events\AttendanceRegistered;
use App\Exceptions\ApiException;
use App\Models\Attendance;
use App\Models\ClassSession;
use App\Models\User;
use App\Models\UserDetail;
use App\Services\NotificationService;
use Illuminate\Support\Carbon;

class RegisterNfcAttendanceService
{
    public function __construct(protected NotificationService $notifications) {}

    public function register(User $teacher, int $sessionId, string $nfcUid, string $scannedAt): Attendance
    {
        $session = ClassSession::with('schedule.settings')->find($sessionId);

        if ($session === null || $session->teacher_id !== $teacher->id) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }

        if ($session->status !== 'ABIERTA') {
            throw ApiException::notFound('La sesión de clase no existe o ya fue cerrada.', 'SES02');
        }

        $userDetail = UserDetail::where('nfc_uid', $nfcUid)->first();

        if ($userDetail === null) {
            throw ApiException::notFound('Tarjeta NFC no reconocida.', 'USR02');
        }

        $student = $userDetail->user;

        if ($student->group_id !== $session->schedule->group_id) {
            throw ApiException::forbidden('Este alumno no pertenece al grupo de esta clase.', 'ATT02');
        }

        if (Attendance::where('class_session_id', $session->id)->where('student_id', $student->id)->exists()) {
            throw ApiException::conflict('Este alumno ya registró su asistencia en esta sesión.', 'ATT01');
        }

        $scannedAtCarbon = Carbon::parse($scannedAt);
        $status = $this->resolveStatus($session, $scannedAtCarbon);

        $attendance = Attendance::create([
            'class_session_id' => $session->id,
            'student_id' => $student->id,
            'schedule_id' => $session->schedule_id,
            'devices_id' => $session->device_id,
            'registered_at' => $scannedAtCarbon,
            'status' => $status,
            'method' => 'NFC',
        ]);

        AttendanceRegistered::dispatch($attendance, $teacher->id);

        if ($status === 'RETARDO') {
            $this->notifications->notifyLate($attendance);
        }

        return $attendance->load('student');
    }

    private function resolveStatus(ClassSession $session, Carbon $scannedAt): string
    {
        $presentTolerance = $session->schedule->settings?->present_tolerance_minutes ?? 10;

        $scheduleStart = Carbon::parse($session->date->format('Y-m-d').' '.$session->schedule->start_time);
        $diffMinutes = ($scannedAt->getTimestamp() - $scheduleStart->getTimestamp()) / 60;

        return $diffMinutes <= $presentTolerance ? 'PRESENTE' : 'RETARDO';
    }
}
