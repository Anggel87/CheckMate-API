<?php

namespace App\Http\Resources;

use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Attendance */
class StudentAttendanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $sessionOpenedAt = $this->classSession?->opened_at;

        return [
            'id' => $this->id,
            'date' => $this->registered_at->format('Y-m-d'),
            'subject' => [
                'id' => $this->schedule->subject->id,
                'name' => $this->schedule->subject->name,
            ],
            'status' => $this->status,
            'checked_in_at' => $this->registered_at->toIso8601String(),
            'class_start_time' => substr((string) $this->schedule->start_time, 0, 5),
            'class_end_time' => substr((string) $this->schedule->end_time, 0, 5),
            'session_opened_at' => $sessionOpenedAt?->toIso8601String(),
            'check_in_delay_minutes' => $sessionOpenedAt && $this->status !== 'FALTA'
                ? max(0, $sessionOpenedAt->diffInMinutes($this->registered_at))
                : null,
        ];
    }
}
