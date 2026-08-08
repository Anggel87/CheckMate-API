<?php

namespace App\Http\Controllers\Director;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Director\ClaimActionRequest;
use App\Http\Resources\TutorClaimResource;
use App\Models\Claim;
use App\Services\ClaimActionService;
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
            ->when($request->query('group_id'), fn ($query, $groupId) => $query->whereHas(
                'attendance.schedule',
                fn ($scheduleQuery) => $scheduleQuery->where('group_id', $groupId)
            ))
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(20);

        return $this->paginatedResponse('Reclamos obtenidos correctamente.', $claims, TutorClaimResource::collection($claims));
    }

    public function show(Request $request, int $claim): JsonResponse
    {
        $claimModel = $this->scopedQuery($request->user()->id)->find($claim);

        if ($claimModel === null) {
            throw ApiException::notFound('La reclamación solicitada no existe.', 'CLM01');
        }

        return $this->successResponse('Reclamo obtenido correctamente.', new TutorClaimResource($claimModel));
    }

    public function action(ClaimActionRequest $request, int $claim, ClaimActionService $service): JsonResponse
    {
        $claimModel = $this->scopedQuery($request->user()->id)->find($claim);

        if ($claimModel === null) {
            throw ApiException::notFound('La reclamación solicitada no existe.', 'CLM01');
        }

        $data = $request->validated();

        $claimModel = $service->act($request->user(), $claimModel, $data['action'], $data['comment'] ?? null);

        return $this->successResponse('Reclamo actualizado correctamente.', [
            'id' => $claimModel->id,
            'status' => $claimModel->status,
            'action_by' => $request->user()->fullName(),
            'action_at' => $claimModel->action_at->toIso8601String(),
            'comment' => $claimModel->comment,
        ]);
    }

    private function scopedQuery(int $directorId): Builder
    {
        return Claim::query()
            ->where('director_id', $directorId)
            ->with(['tutor', 'attendance.schedule.subject', 'attendance.schedule.group.career']);
    }
}
