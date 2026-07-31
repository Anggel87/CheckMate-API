<?php

namespace App\Support;

use App\Models\Schedule;
use Illuminate\Support\Collection;

class ScheduleFormatter
{
    /**
     * @param  Collection<int, Schedule>  $schedules
     */
    public static function summarize(Collection $schedules): string
    {
        return $schedules
            ->map(fn (Schedule $schedule) => sprintf(
                '%s %s-%s',
                $schedule->day_of_week,
                substr((string) $schedule->start_time, 0, 5),
                substr((string) $schedule->end_time, 0, 5),
            ))
            ->implode(', ');
    }
}
