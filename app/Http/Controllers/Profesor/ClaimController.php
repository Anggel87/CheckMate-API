<?php

namespace App\Http\Controllers\Profesor;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\TeacherClaimResource;
use App\Models\Claim;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClaimController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $claims = $this->scopedQuery($request->user()->id)
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('group_id'), fn ($query, $groupId) => $query->whereHas(
                'attendance.schedule',
                fn ($scheduleQuery) => $scheduleQuery->where('group_id', $groupId)
            ))
            ->latest()
            ->paginate(20);

        return $this->paginatedResponse('Reclamos obtenidos correctamente.', $claims, TeacherClaimResource::collection($claims));
    }

    public function show(Request $request, int $claim): JsonResponse
    {
        $claimModel = $this->scopedQuery($request->user()->id)->find($claim);

        if ($claimModel === null) {
            throw ApiException::notFound('La reclamación solicitada no existe.', 'CLM01');
        }

        return $this->successResponse('Reclamo obtenido correctamente.', new TeacherClaimResource($claimModel));
    }

    private function scopedQuery(int $teacherId): Builder
    {
        return Claim::query()
            ->whereHas('attendance.schedule', fn ($query) => $query->where('teacher_id', $teacherId))
            ->with(['tutor', 'attendance.schedule.subject', 'attendance.schedule.group.career']);
    }
}
