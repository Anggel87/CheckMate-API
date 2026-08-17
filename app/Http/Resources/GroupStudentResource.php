<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class GroupStudentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'first_surname' => $this->first_surname,
            'second_surname' => $this->second_surname,
            'email' => $this->email,
            'active' => $this->active,
            'photo' => $this->photo,
            'attendance_rate' => $this->when(isset($this->attendance_rate), fn () => (int) $this->attendance_rate),
            'absence_count' => $this->when(isset($this->absence_count), fn () => (int) $this->absence_count),
            'late_count' => $this->when(isset($this->late_count), fn () => (int) $this->late_count),
            'justification_count' => $this->when(isset($this->justification_count), fn () => (int) $this->justification_count),
            'today_present_count' => $this->when(isset($this->today_present_count), fn () => (int) $this->today_present_count),
            'today_absent_count' => $this->when(isset($this->today_absent_count), fn () => (int) $this->today_absent_count),
            'weekly_absence_count' => $this->when(isset($this->weekly_absence_count), fn () => (int) $this->weekly_absence_count),
        ];
    }
}
