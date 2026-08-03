<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class StudentProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'second_name' => $this->second_name,
            'first_surname' => $this->first_surname,
            'second_surname' => $this->second_surname,
            'email' => $this->email,
            'phone' => $this->phone,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'gender' => $this->gender,
            'photo' => $this->photo,
            'group' => $this->whenLoaded('group', fn () => [
                'id' => $this->group->id,
                'grade' => $this->group->grade,
                'section' => $this->group->section,
            ]),
            'career' => $this->whenLoaded('group', fn () => $this->group->relationLoaded('career') ? [
                'id' => $this->group->career->id,
                'name' => $this->group->career->name,
            ] : null),
        ];
    }
}
