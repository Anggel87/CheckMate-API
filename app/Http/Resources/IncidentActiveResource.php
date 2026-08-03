<?php

namespace App\Http\Resources;

use App\Models\Incident;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Incident */
class IncidentActiveResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $group = $this->schedule->group;

        return [
            'id' => $this->id,
            'type' => $this->type,
            'severity' => $this->severity,
            'status' => $this->status,
            'reporter' => [
                'id' => $this->reporter->id,
                'full_name' => $this->reporter->fullName(),
            ],
            'groups' => [
                [
                    'id' => $group->id,
                    'grade' => $group->grade,
                    'section' => $group->section,
                ],
            ],
        ];
    }
}
