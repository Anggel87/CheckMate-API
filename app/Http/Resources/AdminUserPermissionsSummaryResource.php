<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserPermissionsSummaryResource extends JsonResource
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
            'overrides_count' => $this->when(isset($this->permission_overrides_count), $this->permission_overrides_count),
        ];
    }
}
