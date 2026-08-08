<?php

namespace App\Http\Resources;

use App\Models\AcademicTutor;
use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Group */
class DirectorGroupResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_year_id' => $this->school_year_id,
            'grade' => $this->grade,
            'section' => $this->section,
            'shift' => $this->shift,
            'is_active' => $this->is_active,
            'student_count' => $this->when(isset($this->student_count), fn () => (int) $this->student_count),
            'academic_tutors' => $this->whenLoaded('academicTutors', fn () => $this->academicTutors->map(fn (AcademicTutor $tutor) => [
                'id' => $tutor->id,
                'full_name' => $tutor->user->fullName(),
            ])),
            'attendance_summary' => $this->when(isset($this->attendance_summary), fn () => $this->attendance_summary),
        ];
    }
}
