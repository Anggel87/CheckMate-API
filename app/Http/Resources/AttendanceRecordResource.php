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
        return [
            'attendance_id' => $this->id,
            'date' => $this->registered_at->format('Y-m-d'),
            'status' => $this->status,
            'justifiable' => $this->status === 'FALTA' && ! $this->justification,
        ];
    }
}
