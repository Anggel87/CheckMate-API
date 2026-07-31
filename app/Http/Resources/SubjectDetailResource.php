<?php

namespace App\Http\Resources;

use App\Support\ScheduleFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wraps a stdClass with ->subject (Subject), ->schedules (Collection<Schedule>)
 * and ->attendanceSummary (array{on_time:int,late:int,absent:int}).
 */
class SubjectDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $firstSchedule = $this->schedules->first();

        return [
            'id' => $this->subject->id,
            'name' => $this->subject->name,
            'teacher' => [
                'id' => $firstSchedule->teacher->id,
                'full_name' => $firstSchedule->teacher->fullName(),
                'photo' => $firstSchedule->teacher->photo,
            ],
            'classroom' => $firstSchedule->classroom->name,
            'schedule' => ScheduleFormatter::summarize($this->schedules),
            'attendance_summary' => $this->attendanceSummary,
        ];
    }
}
