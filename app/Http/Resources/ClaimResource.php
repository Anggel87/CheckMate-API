<?php

namespace App\Http\Resources;

use App\Models\Claim;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin Claim */
class ClaimResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $schedule = $this->attendance?->schedule;

        return [
            'id' => $this->id,
            'subject' => $schedule ? [
                'id' => $schedule->subject->id,
                'name' => $schedule->subject->name,
            ] : null,
            'teacher' => $schedule ? [
                'id' => $schedule->teacher->id,
                'full_name' => $schedule->teacher->fullName(),
            ] : null,
            'description' => $this->description,
            'evidence_url' => $this->evidence ? Storage::disk('public')->url($this->evidence) : null,
            'status' => $this->status,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
