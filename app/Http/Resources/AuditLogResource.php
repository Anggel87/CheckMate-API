<?php

namespace App\Http\Resources;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AuditLog */
class AuditLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'entity' => $this->entity,
            'entity_id' => $this->entity_id,
            'action' => $this->action,
            'performed_by' => $this->whenLoaded('performedBy', fn () => $this->performedBy ? [
                'id' => $this->performedBy->id,
                'full_name' => $this->performedBy->fullName(),
            ] : null),
            'before' => $this->before,
            'after' => $this->after,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
