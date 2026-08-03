<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tutor\ReviewJustificationRequest;
use App\Services\Tutor\ReviewJustificationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class JustificationController extends Controller
{
    use ApiResponse;

    public function update(
        ReviewJustificationRequest $request,
        int $student,
        int $justification,
        ReviewJustificationService $service
    ): JsonResponse {
        $data = $request->validated();

        $justificationModel = $service->review(
            $request->user(),
            $student,
            $justification,
            $data['status'],
            $data['comment'] ?? null,
        );

        return $this->successResponse('Justificante revisado correctamente.', [
            'justification_id' => $justificationModel->id,
            'student_id' => $justificationModel->attendance->student_id,
            'status' => $justificationModel->status,
            'reviewed_by' => $request->user()->fullName(),
            'reviewed_at' => $justificationModel->reviewed_at->toIso8601String(),
            'comment' => $justificationModel->comment,
        ]);
    }
}
