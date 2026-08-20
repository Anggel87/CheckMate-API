<?php

namespace App\Http\Controllers\Alumno;

use App\Exceptions\ApiException;
use App\Http\Controllers\Concerns\RequiresDeletionConfirmation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Alumno\StoreClaimRequest;
use App\Http\Requests\Alumno\UpdateClaimRequest;
use App\Http\Requests\Concerns\ValidatesEvidenceFile;
use App\Http\Resources\ClaimResource;
use App\Models\Claim;
use App\Models\Schedule;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ClaimController extends Controller
{
    use ApiResponse, ValidatesEvidenceFile, RequiresDeletionConfirmation;

    public function index(Request $request): JsonResponse
    {
        $claims = Claim::query()
            ->where('tutor_id', $request->user()->id)
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->with(['attendance.schedule.subject', 'attendance.schedule.teacher'])
            ->latest()
            ->paginate(20);

        return $this->paginatedResponse('Reclamos obtenidos correctamente.', $claims, ClaimResource::collection($claims));
    }

    public function show(Request $request, Claim $claim): JsonResponse
    {
        $claim->load(['attendance.schedule.subject', 'attendance.schedule.teacher']);

        if ($claim->tutor_id !== $request->user()->id) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }

        return $this->successResponse('Reclamo obtenido correctamente.', new ClaimResource($claim));
    }

    public function store(StoreClaimRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $this->assertValidEvidence($request->file('evidence'));

        [$attendanceId, $directorId] = empty($data['subject_id'])
            ? $this->resolveGeneralClaim($user)
            : $this->resolveSubjectClaim($user, (int) $data['subject_id']);

        $evidencePath = $request->hasFile('evidence')
            ? $request->file('evidence')->store('claims', 'public')
            : null;

        $claim = Claim::create([
            'attendance_id' => $attendanceId,
            'tutor_id' => $user->id,
            'director_id' => $directorId,
            'description' => $data['description'],
            'evidence' => $evidencePath,
            'status' => 'PENDIENTE',
        ]);

        $claim->load(['attendance.schedule.subject', 'attendance.schedule.teacher']);

        return $this->successResponse('Reclamo creado correctamente.', new ClaimResource($claim), 201);
    }

    /**
     * Igual que destroy(): el alumno solo puede editar su propio reclamo mientras
     * siga en PENDIENTE (nadie lo ha atendido todavia).
     */
    public function update(UpdateClaimRequest $request, Claim $claim): JsonResponse
    {
        if ($claim->tutor_id !== $request->user()->id) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }

        if ($claim->status !== 'PENDIENTE') {
            throw ApiException::conflict('No puedes editar un reclamo que ya esta siendo atendido.', 'CLM03');
        }

        $data = $request->validated();
        $this->assertValidEvidence($request->file('evidence'));

        $claim->description = $data['description'];

        if ($request->hasFile('evidence')) {
            if ($claim->evidence) {
                Storage::disk('public')->delete($claim->evidence);
            }

            $claim->evidence = $request->file('evidence')->store('claims', 'public');
        }

        $claim->save();
        $claim->load(['attendance.schedule.subject', 'attendance.schedule.teacher']);

        return $this->successResponse('Reclamo actualizado correctamente.', new ClaimResource($claim));
    }

    /**
     * El alumno solo puede cancelar su propio reclamo mientras siga en PENDIENTE,
     * es decir, mientras ningun tutor/director/administrador lo haya atendido aun
     * (ver ClaimActionService::act(), que es lo unico que mueve el status fuera de
     * PENDIENTE).
     */
    public function destroy(Request $request, Claim $claim): JsonResponse
    {
        if ($claim->tutor_id !== $request->user()->id) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }

        if ($claim->status !== 'PENDIENTE') {
            throw ApiException::conflict('No puedes cancelar un reclamo que ya esta siendo atendido.', 'CLM03');
        }

        $this->ensureConfirmed($request);

        $claim->delete();

        return $this->successResponse('Reclamo cancelado correctamente.');
    }

    /**
     * Reclamo ligado a una materia: exige que el alumno curse esa materia y que
     * tenga al menos un registro de asistencia en ella (es lo que se reclama).
     *
     * @return array{0: int, 1: int}
     */
    private function resolveSubjectClaim(User $user, int $subjectId): array
    {
        $this->assertEnrolledInSubject($user->group_id, $subjectId);

        // NOTA: claims.attendance_id es NN pero el .md no manda un attendance_id en el
        // body de este endpoint; se usa el registro de asistencia más reciente de esa
        // materia como el que se está reclamando. Ver limitación documentada.
        $attendance = $user->attendances()
            ->whereHas('schedule', fn ($query) => $query->where('subject_id', $subjectId))
            ->latest('registered_at')
            ->first();

        if ($attendance === null) {
            throw ApiException::conflict('No tienes registros de asistencia en esta materia para reclamar todavía.', 'ATT03');
        }

        return [$attendance->id, $attendance->schedule->group->career->director_id];
    }

    /**
     * Reclamo general (quejas no ligadas a una materia, ej. instalaciones o una
     * situación con otro alumno): no hay asistencia que reclamar, se enruta
     * directo al director de la carrera del alumno.
     *
     * @return array{0: null, 1: int}
     */
    private function resolveGeneralClaim(User $user): array
    {
        $group = $user->group()->with('career')->first();

        if ($group === null) {
            throw ApiException::conflict('No tienes un grupo asignado para enviar reclamos.', 'GRP05');
        }

        return [null, $group->career->director_id];
    }

    private function assertEnrolledInSubject(?int $groupId, int $subjectId): void
    {
        $enrolled = Schedule::query()
            ->where('group_id', $groupId)
            ->where('subject_id', $subjectId)
            ->where('is_active', true)
            ->whereHas('schoolYear', fn ($query) => $query->where('status', 'ACTIVO'))
            ->exists();

        if (! $enrolled) {
            throw ApiException::forbidden('No puedes crear un reclamo en una materia que no cursas.', 'PERM02');
        }
    }
}
