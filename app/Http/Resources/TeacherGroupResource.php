<?php

namespace App\Http\Resources;

use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Group */
class TeacherGroupResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'grade' => $this->grade,
            'section' => $this->section,
            'shift' => $this->shift,
            'career' => [
                'id' => $this->career->id,
                'short_name' => $this->career->short_name,
            ],
            'student_count' => (int) $this->student_count,
        ];
    }
}
