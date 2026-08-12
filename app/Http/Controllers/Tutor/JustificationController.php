<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Concerns\ValidatesEvidenceFile;
use App\Http\Requests\Tutor\ReviewJustificationRequest;
use App\Http\Requests\Tutor\StoreJustificationRequest;
use App\Http\Resources\JustificationResource;
use App\Services\Tutor\CreateJustificationService;
use App\Services\Tutor\ReviewJustificationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class JustificationController extends Controller
{
    use ApiResponse, ValidatesEvidenceFile;

    public function store(
        StoreJustificationRequest $request,
        int $student,
        CreateJustificationService $service
    ): JsonResponse {
        $data = $request->validated();

        $this->assertValidEvidence($request->file('evidence'));

        $justification = $service->create(
            $request->user(),
            $student,
            $data['attendance_id'],
            $data['reason'],
            $request->file('evidence'),
        );

        return $this->successResponse('Justificante creado correctamente.', new JustificationResource($justification), 201);
    }

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
