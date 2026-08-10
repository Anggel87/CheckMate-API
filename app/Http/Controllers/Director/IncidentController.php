<?php

namespace App\Http\Controllers\Director;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\LoadsIncidentHistory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Concerns\ValidatesEvidenceFile;
use App\Http\Requests\Director\CloseIncidentRequest;
use App\Http\Requests\Director\StoreIncidentRequest;
use App\Http\Requests\Director\UpdateIncidentRequest;
use App\Http\Requests\Director\UpdateIncidentStudentsRequest;
use App\Http\Resources\IncidentActiveResource;
use App\Http\Resources\IncidentDetailResource;
use App\Http\Resources\IncidentResource;
use App\Models\Incident;
use App\Models\Schedule;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Director\CareerScope;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    use ApiResponse, LoadsIncidentHistory, ValidatesEvidenceFile;

    public function index(Request $request, CareerScope $scope): JsonResponse
    {
        $careerIds = $scope->assertHasCareer($request->user());
        $groupIds = $scope->groupIds($careerIds);

        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        if ($dateFrom !== null && $dateTo !== null && $dateTo < $dateFrom) {
            throw ApiException::unprocessable('El rango de fechas es inválido.', 'VAL02');
        }

        $incidents = Incident::query()
            ->whereHas('schedule', fn ($query) => $query->whereIn('group_id', $groupIds))
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->query('severity'), fn ($query, $severity) => $query->where('severity', $severity))
            ->when($dateFrom, fn ($query, $date) => $query->whereDate('incident_at', '>=', $date))
            ->when($dateTo, fn ($query, $date) => $query->whereDate('incident_at', '<=', $date))
            ->latest('incident_at')
            ->paginate(20);

        return $this->paginatedResponse('Incidentes obtenidos correctamente.', $incidents, IncidentResource::collection($incidents));
    }

    public function active(Request $request, CareerScope $scope): JsonResponse
    {
        $careerIds = $scope->assertHasCareer($request->user());
        $groupIds = $scope->groupIds($careerIds);

        $incidents = Incident::query()
            ->where('status', 'ACTIVO')
            ->whereHas('schedule', fn ($query) => $query->whereIn('group_id', $groupIds))
            ->with(['reporter', 'schedule.group'])
            ->latest('incident_at')
            ->get();

        return $this->successResponse('Incidentes activos obtenidos correctamente.', IncidentActiveResource::collection($incidents));
    }

    public function show(Request $request, int $incident, CareerScope $scope): JsonResponse
    {
        $incidentModel = $this->findIncident($request->user(), $incident, $scope);

        $incidentModel->load(['reporter', 'schedule.group', 'students']);
        $this->loadIncidentHistory($incidentModel);

        return $this->successResponse('Incidente obtenido correctamente.', new IncidentDetailResource($incidentModel));
    }

    public function store(StoreIncidentRequest $request, CareerScope $scope, AuditLogger $auditLogger): JsonResponse
    {
        $director = $request->user();
        $careerIds = $scope->assertHasCareer($director);
        $data = $request->validated();

        $this->assertValidEvidence($request->file('evidence'));

        $schedule = Schedule::whereIn('group_id', $scope->groupIds($careerIds))->find($data['schedule_id']);

        if ($schedule === null) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }

        $evidencePath = $request->hasFile('evidence')
            ? $request->file('evidence')->store('incidents', 'public')
            : null;

        $incident = Incident::create([
            'reported_by_user_id' => $director->id,
            'schedule_id' => $schedule->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'severity' => $data['severity'],
            'evidence' => $evidencePath,
            'incident_at' => now(),
            'status' => 'ACTIVO',
            'reviewed_by_user_id' => $director->id,
            'type' => $data['type'],
        ]);

        foreach ($data['student_ids'] as $studentId) {
            $incident->students()->attach($studentId, [
                'status' => 'DESCONOCIDO',
                'checked_by_user_id' => $director->id,
                'checked_at' => now(),
            ]);
        }

        $incident->load(['reporter', 'schedule.group', 'students']);

        $auditLogger->log('incident', $incident->id, 'CREATE', $director->id, null, [
            'type' => $incident->type,
            'title' => $incident->title,
            'severity' => $incident->severity,
            'status' => $incident->status,
        ]);

        $this->loadIncidentHistory($incident);

        return $this->successResponse('Incidente creado correctamente.', new IncidentDetailResource($incident), 201);
    }

    public function update(UpdateIncidentRequest $request, int $incident, CareerScope $scope, AuditLogger $auditLogger): JsonResponse
    {
        $incidentModel = $this->findIncident($request->user(), $incident, $scope);

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

    public function updateStudents(UpdateIncidentStudentsRequest $request, int $incident, CareerScope $scope, AuditLogger $auditLogger): JsonResponse
    {
        $incidentModel = $this->findIncident($request->user(), $incident, $scope);

        $this->assertNotClosed($incidentModel);

        $data = $request->validated();

        foreach ($data['students'] as $entry) {
            $previousStatus = $incidentModel->students()->where('users.id', $entry['student_id'])->first()?->pivot->status;

            $incidentModel->students()->syncWithoutDetaching([
                $entry['student_id'] => [
                    'status' => $entry['status'],
                    'notes' => $entry['notes'] ?? null,
                    'checked_by_user_id' => $request->user()->id,
                    'checked_at' => now(),
                ],
            ]);

            if ($previousStatus !== $entry['status']) {
                $auditLogger->log(
                    'incident',
                    $incidentModel->id,
                    'UPDATE',
                    $request->user()->id,
                    ['student_id' => $entry['student_id'], 'status' => $previousStatus],
                    ['student_id' => $entry['student_id'], 'status' => $entry['status']],
                );
            }
        }

        return $this->successResponse('Lista de emergencia actualizada correctamente.', [
            'incident_id' => $incidentModel->id,
            'updated_count' => count($data['students']),
        ]);
    }

    public function close(CloseIncidentRequest $request, int $incident, CareerScope $scope, AuditLogger $auditLogger): JsonResponse
    {
        $director = $request->user();
        $incidentModel = $this->findIncident($director, $incident, $scope);

        $this->assertNotClosed($incidentModel);

        $data = $request->validated();
        $status = $data['resolution'] === 'RESOLVED' ? 'RESUELTO' : 'CANCELADO';
        $previousStatus = $incidentModel->status;

        $incidentModel->update([
            'status' => $status,
            'reviewed_by_user_id' => $director->id,
        ]);

        $auditLogger->log(
            'incident',
            $incidentModel->id,
            'UPDATE',
            $director->id,
            ['status' => $previousStatus],
            ['status' => $status, 'reviewed_by_user_id' => $director->id],
        );

        return $this->successResponse('Incidente cerrado correctamente.', [
            'id' => $incidentModel->id,
            'status' => $incidentModel->status,
            'reviewed_by' => $director->fullName(),
        ]);
    }

    private function findIncident(User $director, int $id, CareerScope $scope): Incident
    {
        $incident = Incident::find($id);

        if ($incident === null) {
            throw ApiException::notFound('El incidente solicitado no existe.', 'INC01');
        }

        $careerIds = $scope->careerIds($director);

        if (! $scope->groupIds($careerIds)->contains($incident->schedule->group_id)) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }

        return $incident;
    }

    private function assertNotClosed(Incident $incident): void
    {
        if (in_array($incident->status, ['RESUELTO', 'CANCELADO'], true)) {
            throw ApiException::conflict('No se puede editar o cerrar un incidente ya cerrado o cancelado.', 'INC03');
        }
    }
}
