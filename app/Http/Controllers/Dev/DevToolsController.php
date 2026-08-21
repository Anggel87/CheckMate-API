<?php

namespace App\Http\Controllers\Dev;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\ClassSession;
use App\Models\Schedule;
use App\Services\Profesor\CloseClassSessionService;
use App\Support\DayOfWeek;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Herramientas de desarrollador para probar/demostrar el flujo de asistencia sin
 * esperar a que el reloj real coincida con un horario. Solo se registran en
 * routes/api/dev.php cuando APP_ENV=local (ver routes/api.php).
 */
class DevToolsController extends Controller
{
    use ApiResponse;

    public function activateScheduleNow(Request $request, int $schedule): JsonResponse
    {
        $model = $this->findSchedule($schedule)->load('classroom.devices', 'subject', 'group');

        $device = $model->classroom->devices->firstWhere('is_active', true);

        if ($device === null) {
            throw ApiException::notFound(
                'El salón de este horario no tiene ningún dispositivo activo registrado. Da de alta uno primero (comando device:register o POST /administrador/devices).'
            );
        }

        $duration = min(max((int) $request->input('duration_minutes', 90), 1), 480);
        $now = Carbon::now(config('app.timezone'));

        $model->update([
            'day_of_week' => DayOfWeek::fromCarbon($now),
            'start_time' => $now->copy()->subMinute()->format('H:i:s'),
            'end_time' => $now->copy()->addMinutes($duration)->format('H:i:s'),
            'is_active' => true,
        ]);

        return $this->successResponse('Horario activado como vigente ahora mismo.', [
            'schedule_id' => $model->id,
            'subject' => $model->subject->name,
            'group' => $model->group->grade.'-'.$model->group->section,
            'day_of_week' => $model->day_of_week,
            'start_time' => substr((string) $model->start_time, 0, 5),
            'end_time' => substr((string) $model->end_time, 0, 5),
            'device' => [
                'mac_address' => $device->mac_address,
            ],
            'hint' => 'Pega POST /api/v1/device/nfc con ese mac_address y el nfc_uid del profesor para abrir la sesión.',
        ]);
    }

    public function resetSession(int $schedule): JsonResponse
    {
        $model = $this->findSchedule($schedule);

        $sessions = ClassSession::where('schedule_id', $model->id)->get();
        $count = $sessions->count();

        // attendances.class_session_id tiene cascadeOnDelete() — se borran solas.
        ClassSession::where('schedule_id', $model->id)->delete();

        return $this->successResponse('Sesiones y asistencia de este horario reiniciadas.', [
            'schedule_id' => $model->id,
            'sessions_deleted' => $count,
        ]);
    }

    public function closeSessionNow(int $classSession, CloseClassSessionService $service): JsonResponse
    {
        $session = ClassSession::find($classSession);

        if ($session === null) {
            throw ApiException::notFound('La sesión de clase solicitada no existe.');
        }

        if ($session->status !== 'ABIERTA') {
            throw ApiException::conflict('Esta sesión ya fue cerrada anteriormente.');
        }

        $summary = $service->closeSession($session);

        return $this->successResponse('Sesión cerrada al instante.', $summary);
    }

    public function scheduleStatus(int $schedule): JsonResponse
    {
        $model = $this->findSchedule($schedule)->load('classroom', 'subject', 'group');

        $now = Carbon::now(config('app.timezone'));
        $isCurrentlyActive = $model->is_active
            && $model->day_of_week === DayOfWeek::fromCarbon($now)
            && $model->start_time <= $now->format('H:i:s')
            && $model->end_time >= $now->format('H:i:s');

        $session = ClassSession::where('schedule_id', $model->id)
            ->whereDate('date', $now->toDateString())
            ->with('attendances.student')
            ->first();

        return $this->successResponse('Estado del horario obtenido correctamente.', [
            'schedule_id' => $model->id,
            'subject' => $model->subject->name,
            'group' => $model->group->grade.'-'.$model->group->section,
            'classroom' => $model->classroom->name,
            'day_of_week' => $model->day_of_week,
            'start_time' => substr((string) $model->start_time, 0, 5),
            'end_time' => substr((string) $model->end_time, 0, 5),
            'currently_active' => $isCurrentlyActive,
            'today_session' => $session === null ? null : [
                'id' => $session->id,
                'status' => $session->status,
                'opened_at' => $session->opened_at->toIso8601String(),
                'closed_at' => $session->closed_at?->toIso8601String(),
                'attendances' => $session->attendances->map(fn ($attendance) => [
                    'student_id' => $attendance->student_id,
                    'full_name' => $attendance->student->fullName(),
                    'status' => $attendance->status,
                    'registered_at' => $attendance->registered_at->toIso8601String(),
                ])->values(),
            ],
        ]);
    }

    private function findSchedule(int $id): Schedule
    {
        $schedule = Schedule::find($id);

        if ($schedule === null) {
            throw ApiException::notFound('El horario solicitado no existe.', 'SCH01');
        }

        return $schedule;
    }
}
