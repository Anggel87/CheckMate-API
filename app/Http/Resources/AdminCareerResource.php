<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminCareerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'short_name' => $this->short_name,
            'code' => $this->code,
            'is_active' => $this->is_active,
            'director' => $this->whenLoaded('director', fn () => [
                'id' => $this->director->id,
                'full_name' => $this->director->fullName(),
            ]),
            'groups_count' => $this->whenCounted('groups'),
        ];
    }
}
