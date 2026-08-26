<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\DerivesIncidentGroups;
use App\Models\Incident;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Incident */
class IncidentResource extends JsonResource
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
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
