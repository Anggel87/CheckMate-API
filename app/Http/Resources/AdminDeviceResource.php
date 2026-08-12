<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminDeviceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'mac_address' => $this->mac_address,
            'ip' => $this->ip,
            'is_active' => $this->is_active,
            'classroom_id' => $this->classroom_id,
            'classroom' => $this->whenLoaded('classroom', fn () => [
                'id' => $this->classroom->id,
                'name' => $this->classroom->name,
                'building' => $this->classroom->building,
            ]),
        ];
    }
}
