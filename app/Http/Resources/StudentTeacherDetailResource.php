<?php

namespace App\Http\Resources;

use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wraps a stdClass with ->teacher (User), ->subjects (Collection<Subject>),
 * ->isTutor (bool) and ->schedules (Collection<Schedule>, only the ones shared
 * with the requesting student's group).
 */
class StudentTeacherDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->teacher->id,
            'full_name' => $this->teacher->fullName(),
            'email' => $this->teacher->email,
            'is_tutor' => $this->isTutor,
            'subjects' => $this->subjects->map(fn ($subject) => [
                'id' => $subject->id,
                'name' => $subject->name,
            ])->values(),
            'schedules' => $this->schedules->map(fn (Schedule $schedule) => [
                'schedule_id' => $schedule->id,
                'subject' => [
                    'id' => $schedule->subject->id,
                    'name' => $schedule->subject->name,
                ],
                'day_of_week' => $schedule->day_of_week,
                'start_time' => substr((string) $schedule->start_time, 0, 5),
                'end_time' => substr((string) $schedule->end_time, 0, 5),
                'classroom' => [
                    'name' => $schedule->classroom->name,
                    'building' => $schedule->classroom->building,
                ],
            ])->values(),
        ];
    }
}
