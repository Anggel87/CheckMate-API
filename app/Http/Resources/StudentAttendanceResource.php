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
        return [
            'date' => $this->registered_at->format('Y-m-d'),
            'subject' => [
                'id' => $this->schedule->subject->id,
                'name' => $this->schedule->subject->name,
            ],
            'status' => $this->status,
            'checked_in_at' => $this->registered_at->toIso8601String(),
        ];
    }
}
