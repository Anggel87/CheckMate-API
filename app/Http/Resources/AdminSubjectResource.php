<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminSubjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'schedules_count' => $this->whenCounted('schedules'),
            'careers' => $this->whenLoaded('careers', fn () => $this->careers->map(fn ($career) => [
                'id' => $career->id,
                'name' => $career->name,
                'short_name' => $career->short_name,
            ])->values()),
        ];
    }
}
