<?php

namespace App\Http\Controllers\Tutor;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tutor\ClaimActionRequest;
use App\Http\Resources\TutorClaimResource;
use App\Models\Claim;
use App\Models\User;
use App\Services\Tutor\ClaimActionService;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ClaimController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $groupIds = $this->tutorGroupIds($request->user());

        $claims = $this->scopedQuery($groupIds)
            ->when($request->query('career_id'), fn ($query, $careerId) => $query->whereHas(
                'attendance.schedule.group',
                fn ($groupQuery) => $groupQuery->where('career_id', $careerId)
            ))
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
        $groupIds = $this->tutorGroupIds($request->user());
        $claimModel = $this->scopedQuery($groupIds)->find($claim);

        if ($claimModel === null) {
            throw ApiException::notFound('La reclamación solicitada no existe.', 'CLM01');
        }

        return $this->successResponse('Reclamo obtenido correctamente.', new TutorClaimResource($claimModel));
    }

    public function action(ClaimActionRequest $request, int $claim, ClaimActionService $service): JsonResponse
    {
        $groupIds = $this->tutorGroupIds($request->user());
        $claimModel = $this->scopedQuery($groupIds)->find($claim);

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

    /**
     * @return Collection<int, int>
     */
    private function tutorGroupIds(User $tutor): Collection
    {
        return $tutor->academicTutor?->activeGroups()->pluck('groups.id') ?? collect();
    }

    /**
     * @param  Collection<int, int>  $groupIds
     */
    private function scopedQuery(Collection $groupIds): Builder
    {
        return Claim::query()
            ->whereHas('attendance.schedule', fn ($query) => $query->whereIn('group_id', $groupIds))
            ->with(['tutor', 'attendance.schedule.subject', 'attendance.schedule.group.career']);
    }
}
