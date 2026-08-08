<?php

namespace App\Http\Resources;

use App\Models\Schedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class DirectorTeacherResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'second_name' => $this->second_name,
            'first_surname' => $this->first_surname,
            'second_surname' => $this->second_surname,
            'email' => $this->email,
            'phone' => $this->phone,
            'is_academic_tutor' => $this->whenLoaded('role', fn () => $this->role->name === 'tutor_academico'),
            'schedules_count' => $this->when(isset($this->schedules_count), fn () => (int) $this->schedules_count),
            'schedules' => $this->whenLoaded('schedules', fn () => $this->schedules->map(fn (Schedule $schedule) => [
                'id' => $schedule->id,
                'subject' => $schedule->subject->name,
                'group' => $schedule->group->grade.'-'.$schedule->group->section,
                'day_of_week' => $schedule->day_of_week,
                'start_time' => substr((string) $schedule->start_time, 0, 5),
                'end_time' => substr((string) $schedule->end_time, 0, 5),
            ])),
        ];
    }
}
