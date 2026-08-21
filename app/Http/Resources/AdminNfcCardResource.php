<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminNfcCardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->fullName(),
            'email' => $this->email,
            'role' => $this->whenLoaded('role', fn () => $this->role->name),
            'active' => $this->active,
            'nfc_uid' => $this->whenLoaded('details', fn () => $this->details?->nfc_uid),
        ];
    }
}
