<?php

namespace App\Http\Controllers\Alumno;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Support\DayOfWeek;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $day = $request->query('day');

        if ($day !== null && ! in_array($day, DayOfWeek::all(), true)) {
            throw ApiException::unprocessable('Datos inválidos. Revisa los campos marcados.', 'VAL01', ['day' => ['El día indicado no es válido.']]);
        }

        $schedules = $this->activeSchedulesForGroup($request->user()->group_id)
            ->when($day, fn ($query, $day) => $query->where('day_of_week', $day))
            ->with(['subject', 'teacher', 'classroom'])
            ->get()
            ->groupBy('day_of_week');

        $formatted = $schedules->map(
            fn (Collection $daySchedules) => $daySchedules
                ->sortBy('start_time')
                ->map(fn (Schedule $schedule) => $this->formatSchedule($schedule))
                ->values()
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
            'teacher' => [
                'id' => $schedule->teacher->id,
                'full_name' => $schedule->teacher->fullName(),
            ],
            'classroom' => [
                'name' => $schedule->classroom->name,
                'building' => $schedule->classroom->building,
            ],
            'start_time' => substr((string) $schedule->start_time, 0, 5),
            'end_time' => substr((string) $schedule->end_time, 0, 5),
        ];
    }

    private function activeSchedulesForGroup(?int $groupId): Builder
    {
        return Schedule::query()
            ->where('group_id', $groupId)
            ->where('is_active', true)
            ->whereHas('schoolYear', fn ($query) => $query->where('status', 'ACTIVO'));
    }
}
