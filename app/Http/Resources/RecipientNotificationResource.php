<?php

namespace App\Http\Resources;

use App\Models\AppNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AppNotification */
class RecipientNotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'is_read' => $this->is_read,
            'sent_at' => $this->sent_at?->toIso8601String(),
            'sender' => $this->whenLoaded('sentBy', fn () => $this->sentBy === null ? null : [
                'full_name' => $this->sentBy->fullName(),
            ]),
        ];
    }
}
