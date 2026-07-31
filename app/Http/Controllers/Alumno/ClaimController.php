<?php

namespace App\Http\Controllers\Alumno;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Alumno\StoreClaimRequest;
use App\Http\Requests\Concerns\ValidatesEvidenceFile;
use App\Http\Resources\ClaimResource;
use App\Models\Claim;
use App\Models\Schedule;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClaimController extends Controller
{
    use ApiResponse, ValidatesEvidenceFile;

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

        $this->assertEnrolledInSubject($user->group_id, (int) $data['subject_id']);
        $this->assertValidEvidence($request->file('evidence'));

        // NOTA: claims.attendance_id es NN pero el .md no manda un attendance_id en el
        // body de este endpoint; se usa el registro de asistencia más reciente de esa
        // materia como el que se está reclamando. Ver limitación documentada.
        $attendance = $user->attendances()
            ->whereHas('schedule', fn ($query) => $query->where('subject_id', $data['subject_id']))
            ->latest('registered_at')
            ->first();

        if ($attendance === null) {
            throw ApiException::conflict('No tienes registros de asistencia en esta materia para reclamar todavía.', 'ATT03');
        }

        $evidencePath = $request->hasFile('evidence')
            ? $request->file('evidence')->store('claims', 'public')
            : null;

        $claim = Claim::create([
            'attendance_id' => $attendance->id,
            'tutor_id' => $user->id,
            'director_id' => $attendance->schedule->group->career->director_id,
            'description' => $data['description'],
            'evidence' => $evidencePath,
            'status' => 'PENDIENTE',
        ]);

        $claim->load(['attendance.schedule.subject', 'attendance.schedule.teacher']);

        return $this->successResponse('Reclamo creado correctamente.', new ClaimResource($claim), 201);
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
