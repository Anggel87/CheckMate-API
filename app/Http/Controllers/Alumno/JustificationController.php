<?php

namespace App\Http\Controllers\Alumno;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\RequiresDeletionConfirmation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Alumno\StoreJustificationRequest;
use App\Http\Requests\Alumno\UpdateJustificationRequest;
use App\Http\Requests\Concerns\ValidatesEvidenceFile;
use App\Http\Resources\JustificationResource;
use App\Models\Justification;
use App\Services\Alumno\JustifyAttendanceService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class JustificationController extends Controller
{
    use ApiResponse, ValidatesEvidenceFile, RequiresDeletionConfirmation;

    public function index(Request $request): JsonResponse
    {
        $justifications = Justification::query()
            ->whereHas('attendance', fn ($query) => $query->where('student_id', $request->user()->id))
            ->when($request->query('subject_id'), fn ($query, $subjectId) => $query->whereHas(
                'attendance.schedule',
                fn ($scheduleQuery) => $scheduleQuery->where('subject_id', $subjectId)
            ))
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->with(['attendance.schedule.subject', 'attendance.schedule.teacher', 'reviewedBy'])
            ->latest()
            ->get();

        return $this->successResponse('Justificantes obtenidos correctamente.', JustificationResource::collection($justifications));
    }

    public function show(Request $request, Justification $justification): JsonResponse
    {
        $justification->load('attendance.schedule.subject', 'attendance.schedule.teacher', 'reviewedBy');

        if ($justification->attendance->student_id !== $request->user()->id) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }

        return $this->successResponse('Justificante obtenido correctamente.', new JustificationResource($justification));
    }

    public function store(
        StoreJustificationRequest $request,
        int $subject,
        int $attendance,
        JustifyAttendanceService $service
    ): JsonResponse {
        $data = $request->validated();

        $this->assertValidEvidence($request->file('evidence'));

        $justification = $service->justify(
            $request->user(),
            $subject,
            $attendance,
            $data['reason'],
            $request->file('evidence'),
        );

        return $this->successResponse('Justificante creado correctamente.', new JustificationResource($justification), 201);
    }

    /**
     * Igual que destroy(): solo editable mientras siga PENDIENTE (nadie lo ha
     * revisado todavia). Una vez ACEPTADO/RECHAZADO es inmutable.
     */
    public function update(UpdateJustificationRequest $request, Justification $justification): JsonResponse
    {
        $justification->load('attendance');

        if ($justification->attendance->student_id !== $request->user()->id) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }

        if ($justification->status !== 'PENDIENTE') {
            throw ApiException::conflict('Este justificante ya fue revisado.', 'JUST02');
        }

        $data = $request->validated();
        $this->assertValidEvidence($request->file('evidence'));

        $justification->reason = $data['reason'];

        if ($request->hasFile('evidence')) {
            if ($justification->file) {
                Storage::disk('public')->delete($justification->file);
            }

            $justification->file = $request->file('evidence')->store('justifications', 'public');
        }

        $justification->save();
        $justification->load('attendance.schedule.subject', 'attendance.schedule.teacher', 'reviewedBy');

        return $this->successResponse('Justificante actualizado correctamente.', new JustificationResource($justification));
    }

    public function destroy(Request $request, Justification $justification): JsonResponse
    {
        $justification->load('attendance');

        if ($justification->attendance->student_id !== $request->user()->id) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }

        if ($justification->status !== 'PENDIENTE') {
            throw ApiException::conflict('Este justificante ya fue revisado.', 'JUST02');
        }

        $this->ensureConfirmed($request);

        if ($justification->file) {
            Storage::disk('public')->delete($justification->file);
        }

        $justification->delete();

        return $this->successResponse('Justificante eliminado correctamente.');
    }
}
