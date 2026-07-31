<?php

namespace App\Http\Resources;

use App\Support\ScheduleFormatter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wraps a stdClass with ->subject (Subject) and ->schedules (Collection<Schedule>).
 */
class SubjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $teacher = $this->schedules->first()->teacher;

        return [
            'id' => $this->subject->id,
            'name' => $this->subject->name,
            'teacher' => [
                'id' => $teacher->id,
                'full_name' => $teacher->fullName(),
            ],
            'schedule' => ScheduleFormatter::summarize($this->schedules),
        ];
    }
}
