<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Wraps a stdClass with ->teacher (User), ->subjects (Collection<Subject>) and
 * ->isTutor (bool, whether this teacher is the student's active academic tutor).
 */
class StudentTeacherResource extends JsonResource
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
        ];
    }
}
