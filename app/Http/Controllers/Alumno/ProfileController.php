<?php

namespace App\Http\Controllers\Alumno;

use App\Http\Controllers\Controller;
use App\Http\Resources\StudentProfileResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    use ApiResponse;

    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('group.career');

        return $this->successResponse('Perfil obtenido correctamente.', new StudentProfileResource($user));
    }
}
