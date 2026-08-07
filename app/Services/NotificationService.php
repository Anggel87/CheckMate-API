<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Attendance;
use App\Models\Tutor;
use Closure;

class NotificationService
{
    public function notifyAbsence(Attendance $attendance): void
    {
        $this->notify(
            $attendance,
            type: 'INASISTENCIA',
            preferenceField: 'absences',
            title: 'Inasistencia registrada',
            message: fn (string $studentName, string $subjectName, string $date) => "{$studentName} faltó a {$subjectName} el {$date}.",
        );
    }

    public function notifyLate(Attendance $attendance): void
    {
        $this->notify(
            $attendance,
            type: 'RETARDO',
            preferenceField: 'lates',
            title: 'Retardo registrado',
            message: fn (string $studentName, string $subjectName, string $date) => "{$studentName} llegó tarde a {$subjectName} el {$date}.",
        );
    }

    private function notify(Attendance $attendance, string $type, string $preferenceField, string $title, Closure $message): void
    {
        $attendance->loadMissing(['student.tutors.notificationPreference', 'schedule.subject']);

        $student = $attendance->student;
        $subjectName = $attendance->schedule->subject->name;
        $date = $attendance->registered_at->format('Y-m-d');

        foreach ($student->tutors as $tutor) {
            if (! $this->shouldNotify($tutor, $preferenceField)) {
                continue;
            }

            AppNotification::create([
                'student_id' => $student->id,
                'tutor_id' => $tutor->id,
                'user_id' => null,
                'title' => $title,
                'message' => $message($student->fullName(), $subjectName, $date),
                'type' => $type,
                'is_read' => false,
                'sent_at' => now(),
            ]);
        }
    }

    private function shouldNotify(Tutor $tutor, string $preferenceField): bool
    {
        if (! $tutor->is_active || ! $tutor->pivot->receives_notifications) {
            return false;
        }

        return $tutor->notificationPreference?->{$preferenceField} ?? true;
    }
}
