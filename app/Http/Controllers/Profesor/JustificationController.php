<?php

namespace App\Http\Controllers\Profesor;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\JustificationResource;
use App\Models\Justification;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JustificationController extends Controller
{
    use ApiResponse;

    public function show(Request $request, Justification $justification): JsonResponse
    {
        $justification->load('attendance.schedule.subject', 'reviewedBy');

        $teaches = $justification->attendance->schedule->teacher_id === $request->user()->id;

        if (! $teaches) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }

        return $this->successResponse('Justificante obtenido correctamente.', new JustificationResource($justification));
    }
}
