<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminNotificationSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'recipient_type' => 'TUTOR', // notifications.tutor_id es NOT NULL: todo aviso se entrega al tutor familiar
            'is_read' => $this->is_read,
            'sent_at' => $this->sent_at?->toIso8601String(),
        ];
    }
}
