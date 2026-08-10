<?php

namespace App\Http\Controllers\Profesor;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\LoadsIncidentHistory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Concerns\ValidatesEvidenceFile;
use App\Http\Requests\Profesor\StoreIncidentRequest;
use App\Http\Requests\Profesor\UpdateIncidentRequest;
use App\Http\Requests\Profesor\UpdateIncidentStudentsRequest;
use App\Http\Resources\IncidentActiveResource;
use App\Http\Resources\IncidentDetailResource;
use App\Http\Resources\IncidentResource;
use App\Models\Group;
use App\Models\Incident;
use App\Models\Schedule;
use App\Services\AuditLogger;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    use ApiResponse, LoadsIncidentHistory, ValidatesEvidenceFile;

    public function index(Request $request): JsonResponse
    {
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        if ($dateFrom !== null && $dateTo !== null && $dateTo < $dateFrom) {
            throw ApiException::unprocessable('El rango de fechas es inválido.', 'VAL02');
        }

        $incidents = Incident::query()
            ->where('reported_by_user_id', $request->user()->id)
            ->when($request->query('type'), fn ($query, $type) => $query->where('type', $type))
            ->when($dateFrom, fn ($query, $date) => $query->whereDate('incident_at', '>=', $date))
            ->when($dateTo, fn ($query, $date) => $query->whereDate('incident_at', '<=', $date))
            ->latest('incident_at')
            ->paginate(20);

        return $this->paginatedResponse('Incidentes obtenidos correctamente.', $incidents, IncidentResource::collection($incidents));
    }

    public function active(): JsonResponse
    {
        $incidents = Incident::query()
            ->where('status', 'ACTIVO')
            ->with(['reporter', 'schedule.group'])
            ->latest('incident_at')
            ->get();

        return $this->successResponse('Incidentes activos obtenidos correctamente.', IncidentActiveResource::collection($incidents));
    }

    public function show(Request $request, int $incident): JsonResponse
    {
        $incidentModel = $this->findIncident($incident);

        if ($incidentModel->reported_by_user_id !== $request->user()->id) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }

        $incidentModel->load(['reporter', 'schedule.group', 'students']);
        $this->loadIncidentHistory($incidentModel);

        return $this->successResponse('Incidente obtenido correctamente.', new IncidentDetailResource($incidentModel));
    }

    public function store(StoreIncidentRequest $request, AuditLogger $auditLogger): JsonResponse
    {
        $data = $request->validated();

        $this->assertValidEvidence($request->file('evidence'));

        // incidents.schedule_id es NN pero el .md no manda un schedule_id en el body de
        // este endpoint (un incidente puede afectar a varios grupos vía group_ids). Se
        // usa como ancla el horario activo del profesor en el primer grupo indicado, o
        // cualquier horario activo suyo si no manda group_ids. Ver limitación documentada.
        $schedule = Schedule::where('teacher_id', $request->user()->id)
            ->where('is_active', true)
            ->when(
                ! empty($data['group_ids']),
                fn ($query) => $query->whereIn('group_id', $data['group_ids'])
            )
            ->first() ?? Schedule::where('teacher_id', $request->user()->id)
            ->where('is_active', true)
            ->first();

        if ($schedule === null) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }

        $evidencePath = $request->hasFile('evidence')
            ? $request->file('evidence')->store('incidents', 'public')
            : null;

        $incident = Incident::create([
            'reported_by_user_id' => $request->user()->id,
            'schedule_id' => $schedule->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'severity' => $data['severity'],
            'evidence' => $evidencePath,
            'incident_at' => now(),
            'status' => 'ACTIVO',
            'reviewed_by_user_id' => $request->user()->id,
            'type' => $data['type'],
        ]);

        foreach (($data['group_ids'] ?? []) as $groupId) {
            $group = Group::find($groupId);

            if ($group === null) {
                continue;
            }

            foreach ($group->students()->active()->get() as $student) {
                $incident->students()->attach($student->id, [
                    'status' => 'DESCONOCIDO',
                    'checked_by_user_id' => $request->user()->id,
                    'checked_at' => now(),
                ]);
            }
        }

        $incident->load(['reporter', 'schedule.group', 'students']);

        $auditLogger->log('incident', $incident->id, 'CREATE', $request->user()->id, null, [
            'type' => $incident->type,
            'title' => $incident->title,
            'severity' => $incident->severity,
            'status' => $incident->status,
        ]);

        $this->loadIncidentHistory($incident);

        return $this->successResponse('Incidente creado correctamente.', new IncidentDetailResource($incident), 201);
    }

    public function update(UpdateIncidentRequest $request, int $incident, AuditLogger $auditLogger): JsonResponse
    {
        $incidentModel = $this->findIncident($incident);

        if ($incidentModel->reported_by_user_id !== $request->user()->id) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }

        $this->assertNotClosed($incidentModel);

        $data = $request->validated();

        $this->assertValidEvidence($request->file('evidence'));

        if ($request->hasFile('evidence')) {
            $data['evidence'] = $request->file('evidence')->store('incidents', 'public');
        }

        $editableFields = array_intersect_key($data, array_flip(['type', 'title', 'description', 'severity', 'evidence']));
        $before = $incidentModel->only(array_keys($editableFields));

        $incidentModel->update($editableFields);

        if ($editableFields !== []) {
            $auditLogger->log('incident', $incidentModel->id, 'UPDATE', $request->user()->id, $before, $incidentModel->only(array_keys($editableFields)));
        }

        $incidentModel->load(['reporter', 'schedule.group', 'students']);
        $this->loadIncidentHistory($incidentModel);

        return $this->successResponse('Incidente actualizado correctamente.', new IncidentDetailResource($incidentModel));
    }

    public function updateStudents(UpdateIncidentStudentsRequest $request, int $incident, AuditLogger $auditLogger): JsonResponse
    {
        $incidentModel = $this->findIncident($incident);

        if ($incidentModel->reported_by_user_id !== $request->user()->id) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }

        $this->assertNotClosed($incidentModel);

        $data = $request->validated();

        foreach ($data['students'] as $entry) {
            $newStatus = $entry['present'] ? 'PRESENTE' : 'AUSENTE';
            $previousStatus = $incidentModel->students()->where('users.id', $entry['student_id'])->first()?->pivot->status;

            $incidentModel->students()->syncWithoutDetaching([
                $entry['student_id'] => [
                    'status' => $newStatus,
                    'checked_by_user_id' => $request->user()->id,
                    'checked_at' => now(),
                ],
            ]);

            if ($previousStatus !== $newStatus) {
                $auditLogger->log(
                    'incident',
                    $incidentModel->id,
                    'UPDATE',
                    $request->user()->id,
                    ['student_id' => $entry['student_id'], 'status' => $previousStatus],
                    ['student_id' => $entry['student_id'], 'status' => $newStatus],
                );
            }
        }

        $incidentModel->load('students');
        $present = $incidentModel->students->where('pivot.status', 'PRESENTE')->count();
        $absent = $incidentModel->students->where('pivot.status', 'AUSENTE')->count();

        return $this->successResponse('Lista de emergencia actualizada correctamente.', [
            'incident_id' => $incidentModel->id,
            'updated_students' => count($data['students']),
            'present_count' => $present,
            'absent_count' => $absent,
        ]);
    }

    private function findIncident(int $id): Incident
    {
        $incident = Incident::find($id);

        if ($incident === null) {
            throw ApiException::notFound('El incidente solicitado no existe.', 'INC01');
        }

        return $incident;
    }

    private function assertNotClosed(Incident $incident): void
    {
        if (in_array($incident->status, ['RESUELTO', 'CANCELADO'], true)) {
            throw ApiException::conflict('El incidente ya fue cerrado y no se puede modificar.', 'INC02');
        }
    }
}
