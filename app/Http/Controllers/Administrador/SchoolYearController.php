<?php

namespace App\Http\Controllers\Administrador;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administrador\StoreSchoolYearRequest;
use App\Http\Requests\Administrador\UpdateSchoolYearRequest;
use App\Http\Resources\AdminSchoolYearResource;
use App\Models\SchoolYear;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SchoolYearController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $schoolYears = SchoolYear::query()
            ->withCount('groups')
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->get();

        return $this->successResponse('Ciclos escolares obtenidos correctamente.', AdminSchoolYearResource::collection($schoolYears));
    }

    public function show(int $schoolYear): JsonResponse
    {
        $model = SchoolYear::withCount('groups')->find($schoolYear);

        if ($model === null) {
            throw ApiException::notFound('El ciclo escolar solicitado no existe.', 'SY01');
        }

        return $this->successResponse('Ciclo escolar obtenido correctamente.', new AdminSchoolYearResource($model));
    }

    public function store(StoreSchoolYearRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (SchoolYear::where('name', $data['name'])->exists()) {
            throw ApiException::conflict('Ya existe un ciclo escolar registrado con ese nombre.', 'SY02');
        }

        $schoolYear = SchoolYear::create([...$data, 'status' => 'PROXIMO']);

        return $this->successResponse('Ciclo escolar creado correctamente.', new AdminSchoolYearResource($schoolYear), 201);
    }

    public function update(UpdateSchoolYearRequest $request, int $schoolYear): JsonResponse
    {
        $model = SchoolYear::find($schoolYear);

        if ($model === null) {
            throw ApiException::notFound('El ciclo escolar solicitado no existe.', 'SY01');
        }

        $data = $request->validated();

        if (isset($data['name']) && SchoolYear::where('name', $data['name'])->where('id', '!=', $model->id)->exists()) {
            throw ApiException::conflict('Ya existe un ciclo escolar registrado con ese nombre.', 'SY02');
        }

        if (($data['status'] ?? null) === 'ACTIVO') {
            SchoolYear::where('status', 'ACTIVO')->where('id', '!=', $model->id)->update(['status' => 'FINALIZADO']);
        }

        $model->update($data);

        return $this->successResponse('Ciclo escolar actualizado correctamente.', new AdminSchoolYearResource($model));
    }
}
