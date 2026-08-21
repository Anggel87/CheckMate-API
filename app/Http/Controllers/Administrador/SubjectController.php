<?php

namespace App\Http\Controllers\Administrador;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\RequiresDeletionConfirmation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administrador\StoreSubjectRequest;
use App\Http\Requests\Administrador\UpdateSubjectRequest;
use App\Http\Resources\AdminSubjectResource;
use App\Models\Career;
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
            ->with('careers')
            ->when($request->query('search'), fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->when($request->query('career_id'), fn ($query, $careerId) => $query->whereHas('careers', fn ($query) => $query->where('careers.id', $careerId)))
            ->when($request->has('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->get();

        return $this->successResponse('Materias obtenidas correctamente.', AdminSubjectResource::collection($subjects));
    }

    public function show(int $subject): JsonResponse
    {
        $model = Subject::withCount('schedules')->with('careers')->find($subject);

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

        $careerIds = $data['career_ids'] ?? [];
        $this->assertCareersExist($careerIds);

        $subject = Subject::create(collect($data)->except('career_ids')->all());
        $subject->careers()->sync($careerIds);

        return $this->successResponse('Materia creada correctamente.', new AdminSubjectResource($subject->load('careers')), 201);
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

        if (array_key_exists('career_ids', $data)) {
            $this->assertCareersExist($data['career_ids']);
        }

        $model->update(collect($data)->except('career_ids')->all());

        if (array_key_exists('career_ids', $data)) {
            $model->careers()->sync($data['career_ids']);
        }

        return $this->successResponse('Materia actualizada correctamente.', new AdminSubjectResource($model->load('careers')));
    }

    /**
     * @param  array<int, int>  $careerIds
     */
    private function assertCareersExist(array $careerIds): void
    {
        if ($careerIds === []) {
            return;
        }

        if (Career::whereIn('id', $careerIds)->count() !== count(array_unique($careerIds))) {
            throw ApiException::notFound('Una o mas carreras indicadas no existen.', 'CAR01');
        }
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
