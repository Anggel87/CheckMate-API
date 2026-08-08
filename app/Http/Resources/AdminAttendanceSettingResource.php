<?php

namespace App\Http\Resources;

use App\Models\AttendanceSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AttendanceSetting */
class AdminAttendanceSettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'schedule_id' => $this->schedule_id,
            'present_tolerance_minutes' => $this->present_tolerance_minutes,
            'late_tolerance_minutes' => $this->late_tolerance_minutes,
            'allow_manual_attendance' => $this->allow_manual_attendance,
            'is_active' => $this->is_active,
            'schedule' => $this->whenLoaded('schedule', fn () => [
                'id' => $this->schedule->id,
                'subject' => $this->schedule->subject->name,
                'group' => $this->schedule->group->grade.'-'.$this->schedule->group->section,
                'day_of_week' => $this->schedule->day_of_week,
                'start_time' => substr((string) $this->schedule->start_time, 0, 5),
                'end_time' => substr((string) $this->schedule->end_time, 0, 5),
            ]),
        ];
    }
}
