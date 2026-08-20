<?php

namespace App\Http\Controllers\Administrador;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\RequiresDeletionConfirmation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administrador\StoreClassroomRequest;
use App\Http\Requests\Administrador\UpdateClassroomRequest;
use App\Http\Resources\ClassroomResource;
use App\Models\Classroom;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClassroomController extends Controller
{
    use ApiResponse, RequiresDeletionConfirmation;

    public function index(): JsonResponse
    {
        $classrooms = Classroom::orderBy('building')->orderBy('name')->get();

        return $this->successResponse('Salones obtenidos correctamente.', ClassroomResource::collection($classrooms));
    }

    public function show(int $classroom): JsonResponse
    {
        $model = $this->findClassroom($classroom);

        return $this->successResponse('Salón obtenido correctamente.', new ClassroomResource($model));
    }

    public function store(StoreClassroomRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (Classroom::where('name', $data['name'])->where('building', $data['building'])->exists()) {
            throw ApiException::conflict('Ya existe un salón registrado con ese nombre en ese edificio.', 'CLS02');
        }

        $classroom = Classroom::create($data);

        return $this->successResponse('Salón creado correctamente.', new ClassroomResource($classroom), 201);
    }

    public function update(UpdateClassroomRequest $request, int $classroom): JsonResponse
    {
        $model = $this->findClassroom($classroom);
        $data = $request->validated();

        if (isset($data['name']) || isset($data['building'])) {
            $name = $data['name'] ?? $model->name;
            $building = $data['building'] ?? $model->building;

            if (Classroom::where('name', $name)->where('building', $building)->whereKeyNot($model->id)->exists()) {
                throw ApiException::conflict('Ya existe un salón registrado con ese nombre en ese edificio.', 'CLS02');
            }
        }

        $model->update($data);

        return $this->successResponse('Salón actualizado correctamente.', new ClassroomResource($model));
    }

    public function destroy(Request $request, int $classroom): JsonResponse
    {
        $this->ensureConfirmed($request);

        $model = $this->findClassroom($classroom);

        if ($model->devices()->exists() || $model->schedules()->exists()) {
            throw ApiException::conflict(
                'No se puede eliminar el salón porque tiene dispositivos u horarios asignados.',
                'CLS03'
            );
        }

        $model->delete();

        return $this->successResponse('Salón eliminado correctamente.', new ClassroomResource($model));
    }

    private function findClassroom(int $id): Classroom
    {
        $classroom = Classroom::find($id);

        if ($classroom === null) {
            throw ApiException::notFound('El salón solicitado no existe.', 'CLS01');
        }

        return $classroom;
    }
}
