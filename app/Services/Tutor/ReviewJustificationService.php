<?php

namespace App\Services\Tutor;

use App\Exceptions\ApiException;
use App\Models\Justification;
use App\Models\User;

class ReviewJustificationService
{
    public function review(User $tutor, int $studentId, int $justificationId, string $status, ?string $comment): Justification
    {
        $student = User::find($studentId);

        if ($student === null || ! $student->hasRole('alumno')) {
            throw ApiException::notFound('El usuario solicitado no existe.', 'USR01');
        }

        $tutorGroupIds = $tutor->academicTutor?->activeGroups()->pluck('groups.id') ?? collect();

        if (! $tutorGroupIds->contains($student->group_id)) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }

        $justification = Justification::with('attendance')
            ->whereHas('attendance', fn ($query) => $query->where('student_id', $student->id))
            ->find($justificationId);

        if ($justification === null) {
            throw ApiException::notFound('El justificante solicitado no existe.', 'JUST01');
        }

        if ($justification->status !== 'PENDIENTE') {
            throw ApiException::conflict('Este justificante ya fue revisado.', 'JUST02');
        }

        $justification->update([
            'status' => $status,
            'reviewed_by_user_id' => $tutor->id,
            'reviewed_at' => now(),
            'comment' => $comment,
        ]);

        if ($status === 'ACEPTADO') {
            $justification->attendance()->update(['status' => 'JUSTIFICADA']);
        }

        return $justification->refresh()->load('attendance.schedule.subject');
    }
}
