<?php

namespace App\Http\Resources;

use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Attendance */
class AttendanceRecordResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $latestClaim = $this->claims->sortByDesc('created_at')->first();

        return [
            'attendance_id' => $this->id,
            'subject_id' => $this->schedule->subject_id,
            'date' => $this->registered_at->format('Y-m-d'),
            'status' => $this->status,
            'justifiable' => $this->status === 'FALTA' && ! $this->justification,
            'justification_id' => $this->justification?->id,
            'justification_status' => $this->justification?->status,
            'claim_id' => $latestClaim?->id,
            'claim_status' => $latestClaim?->status,
            'checked_in_at' => $this->registered_at->toIso8601String(),
            'schedule' => [
                'day_of_week' => $this->schedule->day_of_week,
                'start_time' => substr((string) $this->schedule->start_time, 0, 5),
                'end_time' => substr((string) $this->schedule->end_time, 0, 5),
            ],
        ];
    }
}
