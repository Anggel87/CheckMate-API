<?php

namespace App\Http\Controllers\Administrador;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * School-wide equivalent of Director\AuditLogController — same four browsable entities, but
 * never narrowed by CareerScope since the admin can see every student/teacher/group/device.
 */
class AuditLogController extends Controller
{
    use ApiResponse;

    /** @var list<string> */
    private const ENTITIES = ['student', 'teacher', 'group', 'device'];

    public function students(Request $request): JsonResponse
    {
        return $this->indexFor($request, 'student');
    }

    public function teachers(Request $request): JsonResponse
    {
        return $this->indexFor($request, 'teacher');
    }

    public function groups(Request $request): JsonResponse
    {
        return $this->indexFor($request, 'group');
    }

    public function devices(Request $request): JsonResponse
    {
        return $this->indexFor($request, 'device');
    }

    public function show(int $log): JsonResponse
    {
        $logModel = AuditLog::with('performedBy')->find($log);

        if ($logModel === null || ! in_array($logModel->entity, self::ENTITIES, true)) {
            throw ApiException::notFound('El registro solicitado no existe.', 'LOG01');
        }

        return $this->successResponse('Registro de auditoría obtenido correctamente.', new AuditLogResource($logModel));
    }

    private function indexFor(Request $request, string $entity): JsonResponse
    {
        $logs = AuditLog::where('entity', $entity)
            ->with('performedBy')
            ->latest()
            ->paginate(20);

        return $this->paginatedResponse('Registros de auditoría obtenidos correctamente.', $logs, AuditLogResource::collection($logs));
    }
}
