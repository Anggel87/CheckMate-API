<?php

namespace App\Http\Resources;

use App\Models\Justification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Justification */
class JustificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $subject = $this->attendance->schedule->subject;

        return [
            'id' => $this->id,
            'subject' => [
                'id' => $subject->id,
                'name' => $subject->name,
            ],
            'date' => $this->attendance->registered_at->format('Y-m-d'),
            'reason' => $this->reason,
            'evidence_url' => $this->file ? Storage::disk('public')->url($this->file) : null,
            'status' => $this->status,
            'reviewed_by' => $this->whenLoaded('reviewedBy', fn () => $this->reviewedBy ? [
                'id' => $this->reviewedBy->id,
                'full_name' => $this->reviewedBy->fullName(),
            ] : null),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'comment' => $this->comment,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
