<?php

namespace App\Http\Controllers\Administrador;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\RequiresDeletionConfirmation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administrador\StoreCareerRequest;
use App\Http\Requests\Administrador\UpdateCareerRequest;
use App\Http\Resources\AdminCareerResource;
use App\Models\Career;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CareerController extends Controller
{
    use ApiResponse, RequiresDeletionConfirmation;

    public function index(Request $request): JsonResponse
    {
        $careers = Career::query()
            ->withCount('groups')
            ->with('director')
            ->when(! $request->boolean('include_inactive'), fn ($query) => $query->where('is_active', true))
            ->get();

        return $this->successResponse('Carreras obtenidas correctamente.', AdminCareerResource::collection($careers));
    }

    public function show(int $career): JsonResponse
    {
        $model = Career::withCount('groups')->with('director')->find($career);

        if ($model === null) {
            throw ApiException::notFound('La carrera solicitada no existe.', 'CAR01');
        }

        return $this->successResponse('Carrera obtenida correctamente.', new AdminCareerResource($model));
    }

    public function store(StoreCareerRequest $request): JsonResponse
    {
        $data = $request->validated();

        $director = User::whereKey($data['director_id'])
            ->whereHas('role', fn ($query) => $query->where('name', 'director_carrera'))
            ->first();

        if ($director === null) {
            throw ApiException::notFound('El usuario indicado como director no existe o no tiene el rol correcto.', 'USR03');
        }

        if (Career::where('code', $data['code'])->exists()) {
            throw ApiException::conflict('Ya existe una carrera registrada con ese código.', 'CAR02');
        }

        $career = Career::create($data);

        return $this->successResponse('Carrera creada correctamente.', new AdminCareerResource($career), 201);
    }

    public function update(UpdateCareerRequest $request, int $career): JsonResponse
    {
        $model = Career::find($career);

        if ($model === null) {
            throw ApiException::notFound('La carrera solicitada no existe.', 'CAR01');
        }

        $data = $request->validated();

        if (isset($data['director_id'])) {
            $director = User::whereKey($data['director_id'])
                ->whereHas('role', fn ($query) => $query->where('name', 'director_carrera'))
                ->first();

            if ($director === null) {
                throw ApiException::notFound('El usuario indicado como director no existe o no tiene el rol correcto.', 'USR03');
            }
        }

        if (isset($data['code']) && Career::where('code', $data['code'])->where('id', '!=', $model->id)->exists()) {
            throw ApiException::conflict('Ya existe una carrera registrada con ese código.', 'CAR02');
        }

        $model->update($data);

        return $this->successResponse('Carrera actualizada correctamente.', new AdminCareerResource($model));
    }

    public function destroy(Request $request, int $career): JsonResponse
    {
        $this->ensureConfirmed($request);

        $model = Career::find($career);

        if ($model === null) {
            throw ApiException::notFound('La carrera solicitada no existe.', 'CAR01');
        }

        if ($model->groups()->where('is_active', true)->exists()) {
            throw ApiException::conflict('No se puede desactivar una carrera con grupos activos asignados.', 'CAR03');
        }

        $model->update(['is_active' => false]);

        return $this->successResponse('Carrera dada de baja correctamente.', new AdminCareerResource($model));
    }
}
