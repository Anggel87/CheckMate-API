<?php

namespace App\Services\Alumno;

use App\Exceptions\ApiException;
use App\Models\Attendance;
use App\Models\Justification;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class JustifyAttendanceService
{
    public function justify(User $student, int $subjectId, int $attendanceId, string $reason, UploadedFile $evidence): Justification
    {
        $attendance = Attendance::with('schedule')->find($attendanceId);

        if ($attendance === null || $attendance->schedule->subject_id !== $subjectId) {
            throw ApiException::notFound('El registro de asistencia indicado no existe.', 'ATT03');
        }

        if ($attendance->student_id !== $student->id) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }

        if ($attendance->status !== 'FALTA') {
            throw ApiException::conflict('Esta asistencia no puede ser justificada.', 'ATT04');
        }

        if ($attendance->justification()->exists()) {
            throw ApiException::conflict('Ya existe un justificante para esta inasistencia.', 'JUST03');
        }

        $path = $evidence->store('justifications', 'public');

        $justification = Justification::create([
            'attendance_id' => $attendance->id,
            'justified_by_user_id' => $student->id,
            'reason' => $reason,
            'file' => $path,
            'justified_at' => now(),
            'status' => 'PENDIENTE',
        ]);

        return $justification->load('attendance.schedule.subject');
    }
}
