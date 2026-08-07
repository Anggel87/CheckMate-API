<?php

namespace App\Http\Controllers\Administrador;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\RequiresDeletionConfirmation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administrador\StoreSubjectRequest;
use App\Http\Requests\Administrador\UpdateSubjectRequest;
use App\Http\Resources\AdminSubjectResource;
use App\Models\Subject;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    use ApiResponse, RequiresDeletionConfirmation;

    public function index(Request $request): JsonResponse
    {
        $subjects = Subject::query()
            ->withCount('schedules')
            ->when($request->query('search'), fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($request->has('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->get();

        return $this->successResponse('Materias obtenidas correctamente.', AdminSubjectResource::collection($subjects));
    }

    public function show(int $subject): JsonResponse
    {
        $model = Subject::withCount('schedules')->find($subject);

        if ($model === null) {
            throw ApiException::notFound('La materia solicitada no existe.', 'SUBJ01');
        }

        return $this->successResponse('Materia obtenida correctamente.', new AdminSubjectResource($model));
    }

    public function store(StoreSubjectRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (Subject::where('code', $data['code'])->exists()) {
            throw ApiException::conflict('Ya existe una materia registrada con ese código.', 'SUBJ02');
        }

        $subject = Subject::create($data);

        return $this->successResponse('Materia creada correctamente.', new AdminSubjectResource($subject), 201);
    }

    public function update(UpdateSubjectRequest $request, int $subject): JsonResponse
    {
        $model = Subject::find($subject);

        if ($model === null) {
            throw ApiException::notFound('La materia solicitada no existe.', 'SUBJ01');
        }

        $data = $request->validated();

        if (isset($data['code']) && Subject::where('code', $data['code'])->where('id', '!=', $model->id)->exists()) {
            throw ApiException::conflict('Ya existe una materia registrada con ese código.', 'SUBJ02');
        }

        $model->update($data);

        return $this->successResponse('Materia actualizada correctamente.', new AdminSubjectResource($model));
    }

    public function destroy(Request $request, int $subject): JsonResponse
    {
        $this->ensureConfirmed($request);

        $model = Subject::find($subject);

        if ($model === null) {
            throw ApiException::notFound('La materia solicitada no existe.', 'SUBJ01');
        }

        if ($model->schedules()->where('is_active', true)->exists()) {
            throw ApiException::conflict('No se puede desactivar una materia con horarios activos asignados.', 'SUBJ03');
        }

        $model->update(['is_active' => false]);

        return $this->successResponse('Materia dada de baja correctamente.', new AdminSubjectResource($model));
    }
}
