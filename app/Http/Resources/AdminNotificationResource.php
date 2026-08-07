<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminNotificationResource extends JsonResource
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
            'student' => $this->whenLoaded('student', fn () => [
                'id' => $this->student->id,
                'full_name' => $this->student->fullName(),
            ]),
            'tutor' => $this->whenLoaded('tutor', fn () => [
                'id' => $this->tutor->id,
                'full_name' => $this->tutor->fullName(),
            ]),
            'sent_by' => $this->whenLoaded('sentBy', fn () => $this->sentBy === null ? null : [
                'id' => $this->sentBy->id,
                'full_name' => $this->sentBy->fullName(),
            ]),
            'is_read' => $this->is_read,
            'sent_at' => $this->sent_at?->toIso8601String(),
        ];
    }
}
