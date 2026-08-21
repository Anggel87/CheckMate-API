<?php

namespace App\Services;

use App\Models\AppNotification;
use App\Models\Attendance;
use App\Models\Tutor;
use App\Models\User;
use App\Services\WhatsApp\TwilioWhatsAppService;
use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
     * administrador/director. A diferencia de notifyAbsence()/notifyLate(), aquí el
     * llamador decide el tipo, título y mensaje directamente. Entrega por WhatsApp a
     * cada tutor familiar del alumno (comportamiento original, sin cambios).
     *
     * $batchId agrupa todas las filas creadas por un mismo envio (a uno o varios
     * alumnos) para que el log de administrador/director muestre una sola entrada en
     * vez de una por destinatario. Si no se indica, cada llamada genera la suya.
     *
     * @return list<AppNotification>
     */
    public function broadcast(User $student, string $type, string $title, string $message, ?int $sentByUserId = null, ?string $batchId = null): array
    {
        $student->loadMissing('tutors.notificationPreference');

        $preferenceField = self::TYPE_PREFERENCE_FIELDS[$type] ?? 'announcements';
        $batchId ??= (string) Str::uuid();

        $created = [];

        foreach ($student->tutors as $tutor) {
            if (! $this->shouldNotify($tutor, $preferenceField)) {
                continue;
            }

            $created[] = $this->createNotification($tutor, $student->id, $type, $title, $message, $sentByUserId, $batchId);
        }

        return $created;
    }

    /**
     * Entrega directa dentro de la app (sin WhatsApp) — usado cuando el aviso manual
     * de administrador/director elige avisar a un alumno o profesor directamente en
     * vez de a su tutor familiar. $recipient es el propio usuario (alumno o
     * profesor/tutor_academico) que va a ver la notificación al iniciar sesión.
     */
    public function broadcastInApp(
        User $recipient,
        string $recipientType,
        string $type,
        string $title,
        string $message,
        ?int $sentByUserId = null,
        ?string $batchId = null
    ): AppNotification {
        return AppNotification::create([
            'student_id' => $recipientType === 'STUDENT' ? $recipient->id : null,
            'tutor_id' => null,
            'user_id' => $recipient->id,
            'recipient_type' => $recipientType,
            'batch_id' => $batchId ?? (string) Str::uuid(),
            'sent_by_user_id' => $sentByUserId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'is_read' => false,
            'sent_at' => now(),
        ]);
    }

    /**
     * Aviso a TODA la escuela (usado por incidentes/emergencias) — a diferencia de
     * broadcast()/broadcastInApp(), aqui el llamador no elige destinatarios: se
     * resuelven internamente a todos los alumnos activos (WhatsApp a sus tutores +
     * en la app) y a todos los profesores/tutores academicos activos (en la app).
     * Comparte un solo batch_id para que el log de administrador muestre una sola
     * entrada por aviso en vez de una por destinatario.
     */
    public function broadcastSchoolWide(string $type, string $title, string $message, ?int $sentByUserId = null): void
    {
        $batchId = (string) Str::uuid();

        $students = User::query()
            ->whereHas('role', fn ($query) => $query->where('name', 'alumno'))
            ->where('active', true)
            ->get();

        foreach ($students as $student) {
            $this->broadcast($student, $type, $title, $message, $sentByUserId, $batchId);
            $this->broadcastInApp($student, 'STUDENT', $type, $title, $message, $sentByUserId, $batchId);
        }

        $teachers = User::query()
            ->whereHas('role', fn ($query) => $query->whereIn('name', ['profesor', 'tutor_academico']))
            ->where('active', true)
            ->get();

        foreach ($teachers as $teacher) {
            $this->broadcastInApp($teacher, 'TEACHER', $type, $title, $message, $sentByUserId, $batchId);
        }
    }

    private function notify(Attendance $attendance, string $type, string $preferenceField, string $title, Closure $message): void
    {
        $attendance->loadMissing(['student.tutors.notificationPreference', 'schedule.subject']);

        $student = $attendance->student;
        $subjectName = $attendance->schedule->subject->name;
        $date = $attendance->registered_at->format('Y-m-d');
        $batchId = (string) Str::uuid();

        foreach ($student->tutors as $tutor) {
            if (! $this->shouldNotify($tutor, $preferenceField)) {
                continue;
            }

            $this->createNotification($tutor, $student->id, $type, $title, $message($student->fullName(), $subjectName, $date), null, $batchId);
        }
    }

    private function createNotification(
        Tutor $tutor,
        int $studentId,
        string $type,
        string $title,
        string $message,
        ?int $sentByUserId = null,
        ?string $batchId = null
    ): AppNotification {
        $notification = AppNotification::create([
            'student_id' => $studentId,
            'tutor_id' => $tutor->id,
            'user_id' => null,
            'recipient_type' => 'TUTOR',
            'batch_id' => $batchId ?? (string) Str::uuid(),
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
