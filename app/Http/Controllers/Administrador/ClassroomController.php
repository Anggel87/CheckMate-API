<?php

namespace App\Http\Controllers\Administrador;

use App\Http\Controllers\Controller;
use App\Http\Resources\ClassroomResource;
use App\Models\Classroom;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ClassroomController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $classrooms = Classroom::orderBy('building')->orderBy('name')->get();

        return $this->successResponse('Salones obtenidos correctamente.', ClassroomResource::collection($classrooms));
    }
}
