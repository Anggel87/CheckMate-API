<?php

namespace App\Http\Controllers\Administrador;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administrador\StorePermissionOverrideRequest;
use App\Http\Resources\AdminUserPermissionsResource;
use App\Http\Resources\AdminUserPermissionsSummaryResource;
use App\Models\Permission;
use App\Models\User;
use App\Models\UserPermissionOverride;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->with('role')
            ->withCount('permissionOverrides')
            ->when($request->query('search'), fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('first_surname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->when($request->query('role_id'), fn ($query, $roleId) => $query->where('role_id', $roleId))
            ->when($request->has('has_overrides'), fn ($query) => $request->boolean('has_overrides')
                ? $query->has('permissionOverrides')
                : $query->doesntHave('permissionOverrides'))
            ->get();

        return $this->successResponse('Usuarios obtenidos correctamente.', AdminUserPermissionsSummaryResource::collection($users));
    }

    public function show(int $user): JsonResponse
    {
        $model = $this->findUser($user)->load(['role', 'permissionOverrides.permission']);

        return $this->successResponse('Permisos obtenidos correctamente.', new AdminUserPermissionsResource($model));
    }

    public function storeOverride(StorePermissionOverrideRequest $request, int $user): JsonResponse
    {
        $model = $this->findUser($user);
        $data = $request->validated();

        $permission = Permission::find($data['permission_id']);

        if ($permission === null) {
            throw ApiException::notFound('El permiso indicado no existe.', 'PERM03');
        }

        $exists = UserPermissionOverride::where('users_id', $model->id)
            ->where('permissions_id', $permission->id)
            ->exists();

        if ($exists) {
            throw ApiException::conflict('Ya existe una regla de permiso individual para este usuario y permiso.', 'PERM04');
        }

        UserPermissionOverride::create([
            'users_id' => $model->id,
            'permissions_id' => $permission->id,
            'type' => $data['type'],
        ]);

        return $this->successResponse(
            'Permiso individual asignado correctamente.',
            new AdminUserPermissionsResource($model->load(['role', 'permissionOverrides.permission'])),
            201
        );
    }

    public function destroyOverride(int $user, int $override): JsonResponse
    {
        $model = $this->findUser($user);

        $overrideModel = UserPermissionOverride::where('users_id', $model->id)
            ->where('id', $override)
            ->first();

        if ($overrideModel === null) {
            throw ApiException::notFound('La regla de permiso solicitada no existe.', 'PERM05');
        }

        $overrideModel->delete();

        return $this->successResponse(
            'Permiso individual eliminado correctamente.',
            new AdminUserPermissionsResource($model->load(['role', 'permissionOverrides.permission']))
        );
    }

    private function findUser(int $id): User
    {
        $user = User::find($id);

        if ($user === null) {
            throw ApiException::notFound('El usuario solicitado no existe.', 'USR01');
        }

        return $user;
    }
}
