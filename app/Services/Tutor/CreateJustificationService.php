<?php

namespace App\Services\Tutor;

use App\Exceptions\ApiException;
use App\Models\Attendance;
use App\Models\Justification;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class CreateJustificationService
{
    public function create(
        User $tutor,
        int $studentId,
        int $attendanceId,
        string $reason,
        ?UploadedFile $evidence,
    ): Justification {
        $student = User::find($studentId);

        if ($student === null || ! $student->hasRole('alumno')) {
            throw ApiException::notFound('El usuario solicitado no existe.', 'USR01');
        }

        $tutorGroupIds = $tutor->academicTutor?->activeGroups()->pluck('groups.id') ?? collect();

        if (! $tutorGroupIds->contains($student->group_id)) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }

        $attendance = Attendance::find($attendanceId);

        if ($attendance === null || $attendance->student_id !== $student->id) {
            throw ApiException::notFound('El registro de asistencia indicado no existe.', 'ATT03');
        }

        if ($attendance->status !== 'FALTA') {
            throw ApiException::conflict('Esta asistencia no puede ser justificada.', 'ATT04');
        }

        if ($attendance->justification()->exists()) {
            throw ApiException::conflict('Ya existe un justificante para esta inasistencia.', 'JUST03');
        }

        $path = $evidence?->store('justifications', 'public');

        $justification = Justification::create([
            'attendance_id' => $attendance->id,
            'justified_by_user_id' => $tutor->id,
            'reason' => $reason,
            'file' => $path,
            'justified_at' => now(),
            'status' => 'PENDIENTE',
        ]);

        return $justification->load('attendance.schedule.subject');
    }
}
