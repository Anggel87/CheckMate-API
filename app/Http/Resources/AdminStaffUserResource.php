<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class AdminStaffUserResource extends JsonResource
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
            'phone' => $this->phone,
            'role' => $this->whenLoaded('role', fn () => $this->role->name),
            'active' => $this->active,
            'photo_url' => $this->photo ? Storage::disk('public')->url($this->photo) : null,
            'temporary_password' => $this->when(isset($this->temporary_password), $this->temporary_password),
        ];
    }
}
