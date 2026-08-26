<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\DerivesIncidentGroups;
use App\Models\Incident;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Incident */
class IncidentDetailResource extends JsonResource
{
    use DerivesIncidentGroups;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'severity' => $this->severity,
            'status' => $this->status,
            'reporter' => [
                'id' => $this->reporter->id,
                'full_name' => $this->reporter->fullName(),
            ],
            'reviewer' => [
                'id' => $this->reviewer->id,
                'full_name' => $this->reviewer->fullName(),
            ],
            'groups' => $this->affectedGroups(),
            'students' => $this->students->map(fn ($student) => [
                'id' => $student->id,
                'full_name' => $student->fullName(),
                'present' => $student->pivot->status === 'PRESENTE',
                'status' => $student->pivot->status,
                'notes' => $student->pivot->notes,
            ]),
            'evidence_url' => $this->evidence ? Storage::disk('public')->url($this->evidence) : null,
            'history' => $this->when(isset($this->history), fn () => $this->history),
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
