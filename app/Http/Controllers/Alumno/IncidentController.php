<?php

namespace App\Http\Controllers\Alumno;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\IncidentActiveResource;
use App\Models\Incident;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    use ApiResponse;

    public function active(Request $request): JsonResponse
    {
        $incident = Incident::query()
            ->where('status', 'ACTIVO')
            ->with(['reporter', 'schedule.group'])
            ->latest('incident_at')
            ->first();

        if ($incident === null) {
            return $this->successResponse('No hay incidentes activos.', null);
        }

        $alreadyReported = $incident->students()
            ->where('users.id', $request->user()->id)
            ->wherePivot('status', 'SEGURO')
            ->exists();

        return $this->successResponse('Incidente activo obtenido correctamente.', [
            ...(new IncidentActiveResource($incident))->toArray($request),
            'already_reported' => $alreadyReported,
        ]);
    }

    public function reportSafe(Request $request, int $incident): JsonResponse
    {
        $incidentModel = Incident::where('id', $incident)->where('status', 'ACTIVO')->first();

        if ($incidentModel === null) {
            throw ApiException::notFound('No hay un incidente activo con ese id.', 'INC05');
        }

        $student = $request->user();

        $incidentModel->students()->syncWithoutDetaching([
            $student->id => [
                'status' => 'SEGURO',
                'checked_by_user_id' => $student->id,
                'checked_at' => now(),
            ],
        ]);

        return $this->successResponse('Reportaste que estás a salvo. Gracias.', [
            'incident_id' => $incidentModel->id,
            'status' => 'SEGURO',
        ]);
    }
}
