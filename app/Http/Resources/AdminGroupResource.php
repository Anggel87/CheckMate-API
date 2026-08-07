<?php

namespace App\Http\Resources;

use App\Models\AcademicTutor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminGroupResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_year_id' => $this->school_year_id,
            'career_id' => $this->career_id,
            'grade' => $this->grade,
            'section' => $this->section,
            'shift' => $this->shift,
            'is_active' => $this->is_active,
            'academic_tutors' => $this->whenLoaded('academicTutors', fn () => $this->academicTutors->map(fn (AcademicTutor $tutor) => [
                'id' => $tutor->id,
                'full_name' => $tutor->user->fullName(),
            ])),
        ];
    }
}
