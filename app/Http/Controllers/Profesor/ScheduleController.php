<?php

namespace App\Http\Controllers\Profesor;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Support\DayOfWeek;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

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

        $data = $schedules->map(fn (Schedule $schedule) => [
            ...$this->formatSchedule($schedule),
            'session_open' => $schedule->classSessions()
                ->whereDate('date', $today->format('Y-m-d'))
                ->where('status', 'ABIERTA')
                ->exists(),
        ])->values()->all();

        return $this->successResponse('Horario obtenido correctamente.', $data);
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
}
