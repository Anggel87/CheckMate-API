<?php

namespace App\Http\Controllers;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    use ApiResponse;

    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('role');

        return $this->successResponse('Sesion resuelta correctamente.', [
            'id' => $user->id,
            'full_name' => $user->fullName(),
            'email' => $user->email,
            'role' => $user->role->name,
            'permissions' => $user->effectivePermissions(),
        ]);
    }
}
