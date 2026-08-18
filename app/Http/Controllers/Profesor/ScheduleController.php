<?php

namespace App\Http\Controllers\Profesor;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Services\Profesor\ScheduleSessionStateService;
use App\Support\DayOfWeek;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ScheduleController extends Controller
{
    use ApiResponse;

    public function today(Request $request): JsonResponse
    {
        $today = Carbon::now(config('app.timezone'));

        $schedules = Schedule::query()
            ->where('teacher_id', $request->user()->id)
            ->where('is_active', true)
            ->where('day_of_week', DayOfWeek::fromCarbon($today))
            ->with(['subject', 'group', 'classroom'])
            ->get();

        $data = $schedules->map(function (Schedule $schedule) use ($today) {
            $openSession = $schedule->classSessions()
                ->whereDate('date', $today->format('Y-m-d'))
                ->where('status', 'ABIERTA')
                ->first();

            return [
                ...$this->formatSchedule($schedule),
                'session_open' => $openSession !== null,
                'session_id' => $openSession?->id,
            ];
        })->values()->all();

        return $this->successResponse('Horario obtenido correctamente.', $data);
    }

    public function sessionState(Request $request, int $schedule, ScheduleSessionStateService $service): JsonResponse
    {
        $scheduleModel = $this->findOwnedSchedule($request, $schedule);
        $today = Carbon::now(config('app.timezone'))->toDateString();

        $snapshot = $service->snapshot($scheduleModel, $today);

        return $this->successResponse('Estado de la sesión obtenido correctamente.', [
            'session' => $snapshot['session'] ? [
                'id' => $snapshot['session']->id,
                'status' => $snapshot['session']->status,
                'opened_at' => $snapshot['session']->opened_at->toIso8601String(),
            ] : null,
            'students' => $snapshot['students'],
        ]);
    }

    public function stream(Request $request, int $schedule, ScheduleSessionStateService $service): StreamedResponse
    {
        $scheduleModel = $this->findOwnedSchedule($request, $schedule);
        $today = Carbon::now(config('app.timezone'))->toDateString();
        $sinceAttendanceId = (int) $request->query('since_attendance_id', 0);

        return response()->stream(function () use ($scheduleModel, $today, $sinceAttendanceId, $service) {
            set_time_limit(0);

            // PHP only flushes headers to the socket on the first body byte, so without an
            // immediate event the connection looks dead to the client until something actually
            // changes (or the first 15s ping) — send one right away so headers go out at once.
            $this->emitSseEvent('ping', []);

            $deadline = now()->addSeconds(55);
            $lastPing = now();
            $knownSessionId = null;
            $cursor = $sinceAttendanceId;

            while (now()->lessThan($deadline)) {
                if (connection_aborted()) {
                    break;
                }

                $snapshot = $service->snapshot($scheduleModel, $today, $cursor);
                $session = $snapshot['session'];

                if ($session !== null && $session->id !== $knownSessionId) {
                    $knownSessionId = $session->id;
                    $this->emitSseEvent('session', [
                        'id' => $session->id,
                        'status' => $session->status,
                        'opened_at' => $session->opened_at->toIso8601String(),
                    ]);
                }

                foreach ($snapshot['new_attendances'] as $attendance) {
                    $cursor = max($cursor, $attendance->id);
                    $this->emitSseEvent('attendance', [
                        'attendance_id' => $attendance->id,
                        'student_id' => $attendance->student_id,
                        'status' => $attendance->status,
                        'registered_at' => $attendance->registered_at->toIso8601String(),
                    ]);
                }

                if ($session?->status === 'CERRADA') {
                    $this->emitSseEvent('closed', ['id' => $session->id, 'status' => $session->status]);
                    break;
                }

                if (now()->diffInSeconds($lastPing) >= 15) {
                    $this->emitSseEvent('ping', []);
                    $lastPing = now();
                }

                usleep(1_500_000);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    public function week(Request $request): JsonResponse
    {
        $day = $request->query('day');

        if ($day !== null && ! in_array($day, DayOfWeek::all(), true)) {
            throw ApiException::unprocessable('Datos inválidos. Revisa los campos marcados.', 'VAL01', ['day' => ['El día indicado no es válido.']]);
        }

        $schedules = Schedule::query()
            ->where('teacher_id', $request->user()->id)
            ->where('is_active', true)
            ->when($day, fn ($query, $day) => $query->where('day_of_week', $day))
            ->with(['subject', 'group', 'classroom'])
            ->get()
            ->groupBy('day_of_week');

        $formatted = $schedules->map(
            fn (Collection $daySchedules) => $daySchedules->map(fn (Schedule $schedule) => $this->formatSchedule($schedule))->values()
        );

        return $this->successResponse('Horario obtenido correctamente.', $formatted);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatSchedule(Schedule $schedule): array
    {
        return [
            'schedule_id' => $schedule->id,
            'subject' => [
                'id' => $schedule->subject->id,
                'name' => $schedule->subject->name,
            ],
            'group' => [
                'id' => $schedule->group->id,
                'grade' => $schedule->group->grade,
                'section' => $schedule->group->section,
            ],
            'classroom' => [
                'name' => $schedule->classroom->name,
                'building' => $schedule->classroom->building,
            ],
            'start_time' => substr((string) $schedule->start_time, 0, 5),
            'end_time' => substr((string) $schedule->end_time, 0, 5),
        ];
    }

    private function findOwnedSchedule(Request $request, int $scheduleId): Schedule
    {
        $schedule = Schedule::find($scheduleId);

        if ($schedule === null) {
            throw ApiException::notFound('El horario solicitado no existe.', 'SCH01');
        }

        if ($schedule->teacher_id !== $request->user()->id) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }

        return $schedule;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function emitSseEvent(string $event, array $data): void
    {
        echo "event: {$event}\n";
        echo 'data: '.json_encode($data)."\n\n";

        if (ob_get_level() > 0) {
            @ob_flush();
        }

        flush();
    }
}
