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
            'recipient_type' => $this->recipient_type,
            'student' => $this->whenLoaded('student', fn () => $this->student === null ? null : [
                'id' => $this->student->id,
                'full_name' => $this->student->fullName(),
            ]),
            'tutor' => $this->whenLoaded('tutor', fn () => $this->tutor === null ? null : [
                'id' => $this->tutor->id,
                'full_name' => $this->tutor->fullName(),
            ]),
            'teacher' => $this->when($this->recipient_type === 'TEACHER', fn () => $this->user === null ? null : [
                'id' => $this->user->id,
                'full_name' => $this->user->fullName(),
            ]),
            'sent_by' => $this->whenLoaded('sentBy', fn () => $this->sentBy === null ? null : [
                'id' => $this->sentBy->id,
                'full_name' => $this->sentBy->fullName(),
            ]),
            'recipients_count' => $this->recipients_count ?? 1,
            'recipients' => $this->recipients ?? [],
            'is_read' => $this->is_read,
            'sent_at' => $this->sent_at?->toIso8601String(),
        ];
    }
}
