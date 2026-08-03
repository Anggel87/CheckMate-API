<?php

namespace App\Http\Resources;

use App\Models\Claim;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Claim */
class TeacherClaimResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $student = $this->tutor; // claims.tutor_id guarda al alumno que reclama, no a un tutor familiar
        $schedule = $this->attendance->schedule;
        $group = $schedule->group;

        return [
            'id' => $this->id,
            'student' => [
                'id' => $student->id,
                'full_name' => $student->fullName(),
                'group' => "{$group->grade}-{$group->section} {$group->career->short_name}",
            ],
            'subject' => [
                'id' => $schedule->subject->id,
                'name' => $schedule->subject->name,
            ],
            'description' => $this->description,
            'evidence_url' => $this->evidence ? Storage::url($this->evidence) : null,
            'status' => $this->status,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
