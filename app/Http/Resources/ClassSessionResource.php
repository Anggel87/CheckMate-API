<?php

namespace App\Http\Resources;

use App\Models\ClassSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin ClassSession */
class ClassSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'session_id' => $this->id,
            'schedule_id' => $this->schedule_id,
            'date' => $this->date->format('Y-m-d'),
            'status' => $this->status,
            'opened_at' => $this->opened_at->toIso8601String(),
        ];
    }
}
