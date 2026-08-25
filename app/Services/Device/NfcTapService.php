<?php

namespace App\Services\Device;

use App\Events\AttendanceRegistered;
use App\Exceptions\ApiException;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\ClassSession;
use App\Models\Device;
use App\Models\Schedule;
use App\Models\User;
use App\Models\UserDetail;
use App\Services\NotificationService;
use App\Support\DayOfWeek;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class NfcTapService
{
    public function __construct(protected NotificationService $notifications) {}

    public function handle(string $macAddress, string $nfcUid, ?string $scannedAtRaw): ClassSession|Attendance
    {
        $device = Device::where('mac_address', $macAddress)->first();

        if ($device === null) {
            throw ApiException::notFound('El dispositivo solicitado no existe.', 'DEV01');
        }

        if (! $device->is_active) {
            throw ApiException::forbidden('El dispositivo ya se encuentra dado de baja.', 'DEV04');
        }

        $now = Carbon::now(config('app.timezone'));

        $schedule = Schedule::where('classroom_id', $device->classroom_id)
            ->where('is_active', true)
            ->where('day_of_week', DayOfWeek::fromCarbon($now))
            ->where('start_time', '<=', $now->format('H:i:s'))
            ->where('end_time', '>=', $now->format('H:i:s'))
            ->first();

        if ($schedule === null) {
            throw ApiException::notFound('No hay una clase programada en este salón en este momento.', 'SES04');
        }

        $userDetail = UserDetail::where('nfc_uid', $nfcUid)->first();

        if ($userDetail === null) {
            throw ApiException::notFound('Tarjeta NFC no reconocida.', 'USR02');
        }

        $tappedUser = $userDetail->user;

        if ($tappedUser->id === $schedule->teacher_id) {
            return $this->openSession($schedule, $device, $now);
        }

        return $this->registerAttendance($schedule, $tappedUser, $device, $now, $scannedAtRaw);
    }

    private function openSession(Schedule $schedule, Device $device, Carbon $now): ClassSession
    {
        $session = ClassSession::where('schedule_id', $schedule->id)
            ->whereDate('date', $now->toDateString())
            ->first();

        if ($session !== null) {
            if ($session->status === 'ABIERTA') {
                throw ApiException::conflict('Ya existe una sesión abierta para esta clase.', 'SES01');
            }

            throw ApiException::conflict('La sesión ya fue cerrada anteriormente.', 'SES03');
        }

        return ClassSession::create([
            'schedule_id' => $schedule->id,
            'date' => $now->toDateString(),
            'teacher_id' => $schedule->teacher_id,
            'device_id' => $device->id,
            'opened_at' => $now,
            'status' => 'ABIERTA',
            'opening_method' => 'NFC',
            'is_active' => true,
        ]);
    }

    private function registerAttendance(Schedule $schedule, User $student, Device $device, Carbon $now, ?string $scannedAtRaw): Attendance
    {
        if ($student->group_id !== $schedule->group_id) {
            throw ApiException::forbidden('Este alumno no pertenece al grupo de esta clase.', 'ATT02');
        }

        // Bloquea la fila de la sesión para que este tap y un cierre automático concurrente
        // (class-sessions:auto-close) no se entrelacen: sin esto, un tap que llega justo cuando
        // el cron está cerrando la clase puede crear una asistencia NFC a la vez que el cron crea
        // una FALTA por SISTEMA para el mismo alumno, dejando dos registros para la misma sesión.
        $attendance = DB::transaction(function () use ($schedule, $student, $device, $now, $scannedAtRaw) {
            $session = ClassSession::where('schedule_id', $schedule->id)
                ->whereDate('date', $now->toDateString())
                ->where('status', 'ABIERTA')
                ->lockForUpdate()
                ->first();

            if ($session === null) {
                throw ApiException::notFound('La sesión de clase no existe o ya fue cerrada.', 'SES02');
            }

            $existing = Attendance::where('class_session_id', $session->id)
                ->where('student_id', $student->id)
                ->first();

            // Una FALTA es una marca manual del profesor por falta de registro, no una asistencia
            // real: un tap NFC posterior del alumno debe poder sobreescribirla. PRESENTE/RETARDO/
            // JUSTIFICADA sí son registros firmes y siguen bloqueando un segundo tap.
            if ($existing !== null && $existing->status !== 'FALTA') {
                throw ApiException::conflict('Este alumno ya registró su asistencia en esta sesión.', 'ATT01');
            }

            $scannedAt = $scannedAtRaw !== null ? Carbon::parse($scannedAtRaw) : $now;
            $status = $this->resolveStatus($session, $scannedAt);

            return Attendance::updateOrCreate(
                ['class_session_id' => $session->id, 'student_id' => $student->id],
                [
                    'schedule_id' => $schedule->id,
                    'devices_id' => $device->id,
                    'registered_at' => $scannedAt,
                    'status' => $status,
                    'method' => 'NFC',
                ]
            );
        });

        AttendanceRegistered::dispatch($attendance, $student->id);

        // Fuera de la transacción a propósito: manda un WhatsApp real (I/O externo) y no debe
        // mantener el lock de la fila de sesión abierto mientras espera esa llamada de red.
        if ($attendance->status === 'RETARDO') {
            $this->notifications->notifyLate($attendance);
        }

        return $attendance;
    }

    private function resolveStatus(ClassSession $session, Carbon $scannedAt): string
    {
        $settings = $session->schedule->settings;
        $isCustom = $settings !== null && $settings->is_active;

        $presentTolerance = $isCustom ? $settings->present_tolerance_minutes : AttendanceSetting::DEFAULT_PRESENT_TOLERANCE_MINUTES;
        $lateTolerance = $isCustom ? $settings->late_tolerance_minutes : AttendanceSetting::DEFAULT_LATE_TOLERANCE_MINUTES;

        $diffMinutes = ($scannedAt->getTimestamp() - $session->opened_at->getTimestamp()) / 60;

        if ($diffMinutes > $lateTolerance) {
            throw ApiException::conflict(
                'Ya pasó el tiempo límite de tolerancia para esta clase; el registro ya no es válido.',
                'ATT05'
            );
        }

        return $diffMinutes <= $presentTolerance ? 'PRESENTE' : 'RETARDO';
    }
}
