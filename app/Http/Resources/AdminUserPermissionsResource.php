<?php

namespace App\Http\Resources;

use App\Models\Permission;
use App\Models\UserPermissionOverride;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserPermissionsResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->id,
            'full_name' => $this->fullName(),
            'email' => $this->email,
            'role' => $this->whenLoaded('role', fn () => [
                'id' => $this->role->id,
                'name' => $this->role->name,
            ]),
            'role_permissions' => $this->rolePermissions()->map(fn (Permission $permission) => [
                'id' => $permission->id,
                'key_name' => $permission->key_name,
                'name' => $permission->name,
            ])->values(),
            'overrides' => $this->whenLoaded('permissionOverrides', fn () => $this->permissionOverrides->map(fn (UserPermissionOverride $override) => [
                'id' => $override->id,
                'type' => $override->type,
                'permission' => [
                    'id' => $override->permission->id,
                    'key_name' => $override->permission->key_name,
                    'name' => $override->permission->name,
                ],
            ])),
            'effective_permissions' => $this->effectivePermissions(),
        ];
    }
}
