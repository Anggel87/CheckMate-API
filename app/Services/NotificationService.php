<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Attendance;
use App\Models\Tutor;
use App\Models\User;
use App\Services\WhatsApp\TwilioWhatsAppService;
use Closure;
use Illuminate\Support\Facades\Log;
use Throwable;

class NotificationService
{
    /** @var array<string, string> */
    private const TYPE_PREFERENCE_FIELDS = [
        'INASISTENCIA' => 'absences',
        'RETARDO' => 'lates',
        'INCIDENTE' => 'incidents',
        'JUSTIFICANTE' => 'justifications',
        'RECLAMO' => 'claims',
        'RECLAMO_PROFESOR' => 'claims',
        'AVISO' => 'announcements',
    ];

    public function __construct(protected TwilioWhatsAppService $whatsapp) {}

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

    /**
     * Aviso manual (no ligado a una asistencia), disparado desde el panel de
     * administrador. A diferencia de notifyAbsence()/notifyLate(), aquí el
     * llamador decide el tipo, título y mensaje directamente.
     *
     * @return list<AppNotification>
     */
    public function broadcast(User $student, string $type, string $title, string $message, ?int $sentByUserId = null): array
    {
        $student->loadMissing('tutors.notificationPreference');

        $preferenceField = self::TYPE_PREFERENCE_FIELDS[$type] ?? 'announcements';

        $created = [];

        foreach ($student->tutors as $tutor) {
            if (! $this->shouldNotify($tutor, $preferenceField)) {
                continue;
            }

            $created[] = $this->createNotification($tutor, $student->id, $type, $title, $message, $sentByUserId);
        }

        return $created;
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

            $this->createNotification($tutor, $student->id, $type, $title, $message($student->fullName(), $subjectName, $date));
        }
    }

    private function createNotification(
        Tutor $tutor,
        int $studentId,
        string $type,
        string $title,
        string $message,
        ?int $sentByUserId = null
    ): AppNotification {
        $notification = AppNotification::create([
            'student_id' => $studentId,
            'tutor_id' => $tutor->id,
            'user_id' => null,
            'sent_by_user_id' => $sentByUserId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'is_read' => false,
            'sent_at' => now(),
        ]);

        $this->sendWhatsApp($tutor, $title, $message);

        return $notification;
    }

    /**
     * El WhatsApp es un canal adicional a la fila en BD, no la fuente de verdad — si
     * Twilio falla o no está configurado, la notificación ya quedó registrada.
     */
    private function sendWhatsApp(Tutor $tutor, string $title, string $message): void
    {
        try {
            $this->whatsapp->sendMessage($tutor->phone, "{$title}\n\n{$message}");
        } catch (Throwable $e) {
            Log::warning('No se pudo enviar el WhatsApp de la notificación.', [
                'tutor_id' => $tutor->id,
                'error' => $e->getMessage(),
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
