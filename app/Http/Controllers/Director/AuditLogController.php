<?php

namespace App\Http\Controllers\Director;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Director\CareerScope;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AuditLogController extends Controller
{
    use ApiResponse;

    /** @var array<string, string> */
    private const ENTITY_METHODS = [
        'student' => 'studentIds',
        'teacher' => 'teacherIds',
        'group' => 'groupIds',
        'device' => 'deviceIds',
    ];

    public function students(Request $request, CareerScope $scope): JsonResponse
    {
        return $this->indexFor($request, $scope, 'student');
    }

    public function teachers(Request $request, CareerScope $scope): JsonResponse
    {
        return $this->indexFor($request, $scope, 'teacher');
    }

    public function groups(Request $request, CareerScope $scope): JsonResponse
    {
        return $this->indexFor($request, $scope, 'group');
    }

    public function devices(Request $request, CareerScope $scope): JsonResponse
    {
        return $this->indexFor($request, $scope, 'device');
    }

    public function show(Request $request, int $log, CareerScope $scope): JsonResponse
    {
        $logModel = AuditLog::with('performedBy')->find($log);

        if ($logModel === null || ! $this->allowedIds($logModel->entity, $request->user(), $scope)->contains($logModel->entity_id)) {
            throw ApiException::notFound('El registro solicitado no existe.', 'LOG01');
        }

        return $this->successResponse('Registro de auditoría obtenido correctamente.', new AuditLogResource($logModel));
    }

    private function indexFor(Request $request, CareerScope $scope, string $entity): JsonResponse
    {
        $ids = $this->allowedIds($entity, $request->user(), $scope);

        $logs = AuditLog::where('entity', $entity)
            ->whereIn('entity_id', $ids)
            ->with('performedBy')
            ->latest()
            ->paginate(20);

        return $this->paginatedResponse('Registros de auditoría obtenidos correctamente.', $logs, AuditLogResource::collection($logs));
    }

    /**
     * @return Collection<int, int>
     */
    private function allowedIds(string $entity, User $director, CareerScope $scope): Collection
    {
        $method = self::ENTITY_METHODS[$entity] ?? null;

        if ($method === null) {
            return collect();
        }

        $careerIds = $scope->assertHasCareer($director);

        return $scope->{$method}($careerIds);
    }
}
