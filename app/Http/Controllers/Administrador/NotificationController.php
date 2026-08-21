<?php

namespace App\Http\Controllers\Administrador;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Administrador\ResendNotificationRequest;
use App\Http\Requests\Administrador\StoreNotificationRequest;
use App\Http\Resources\AdminNotificationResource;
use App\Http\Resources\AdminNotificationSummaryResource;
use App\Models\AppNotification;
use App\Models\User;
use App\Services\NotificationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    use ApiResponse;

    /**
     * Un envio a N destinatarios crea una fila por destinatario (comparten batch_id),
     * pero el log solo debe mostrar una entrada por envio — se agrupa aqui y se expone
     * el conteo de destinatarios en vez de una fila por cada uno.
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = AppNotification::query()
            ->with(['student', 'tutor'])
            ->when($request->query('type'), fn ($query, $type) => $query->where('type', $type))
            ->when($request->has('is_read'), fn ($query) => $query->where('is_read', $request->boolean('is_read')))
            ->when($request->query('date_from'), fn ($query, $date) => $query->whereDate('sent_at', '>=', $date))
            ->when($request->query('date_to'), fn ($query, $date) => $query->whereDate('sent_at', '<=', $date))
            ->latest('sent_at')
            ->get()
            ->groupBy(fn (AppNotification $n) => $n->batch_id ?? (string) $n->id)
            ->map(fn (Collection $group) => $this->withRecipientSummary($group))
            ->values();

        return $this->successResponse('Notificaciones obtenidas correctamente.', AdminNotificationSummaryResource::collection($notifications));
    }

    public function show(int $notification): JsonResponse
    {
        $model = $this->findNotification($notification)->load(['student', 'tutor', 'user', 'sentBy']);
        $group = $this->batchSiblings($model)->load(['student', 'tutor', 'user']);

        return $this->successResponse('Notificación obtenida correctamente.', new AdminNotificationResource($this->withRecipientSummary($group, $model)));
    }

    /**
     * Marca como leida toda la fila (o el batch completo, si el aviso se envio a
     * varios destinatarios) para que el estado se mantenga consistente sin importar
     * cual fila del batch quede como representante la proxima vez que se liste.
     */
    public function markRead(int $notification): JsonResponse
    {
        $model = $this->findNotification($notification);

        if ($model->batch_id !== null) {
            AppNotification::where('batch_id', $model->batch_id)->update(['is_read' => true]);
        } else {
            $model->update(['is_read' => true]);
        }

        return $this->successResponse('Notificación marcada como leída.', new AdminNotificationSummaryResource($model->fresh()));
    }

    public function store(StoreNotificationRequest $request, NotificationService $service): JsonResponse
    {
        $data = $request->validated();

        $created = $this->deliver($data, $service, $request->user()->id);

        if ($created->isEmpty()) {
            throw ApiException::unprocessable('Debes indicar al menos un destinatario válido.', 'NOT02');
        }

        $first = $created->first();

        return $this->successResponse('Aviso enviado correctamente.', [
            'id' => $first->id,
            'title' => $data['title'],
            'type' => $data['type'],
            'recipients_count' => $created->count(),
            'sent_at' => $first->sent_at?->toIso8601String(),
        ], 201);
    }

    public function resend(ResendNotificationRequest $request, NotificationService $service, int $notification): JsonResponse
    {
        $original = $this->findNotification($notification);
        $data = $request->validated();

        if (! isset($data['target'])) {
            $created = $this->resendToOriginalRecipients($this->batchSiblings($original), $service, $request->user()->id);
        } else {
            $created = $this->deliver([
                ...$data,
                'type' => $original->type,
                'title' => $original->title,
                'message' => $original->message,
            ], $service, $request->user()->id);
        }

        if ($created->isEmpty()) {
            throw ApiException::unprocessable('Debes indicar al menos un destinatario válido.', 'NOT02');
        }

        $first = $created->first();

        return $this->successResponse('Aviso reenviado correctamente.', [
            'id' => $first->id,
            'original_notification_id' => $original->id,
            'recipients_count' => $created->count(),
            'sent_at' => $first->sent_at?->toIso8601String(),
        ], 201);
    }

    /**
     * Resuelve el destino (target) y lo entrega por el canal correcto:
     * - target=TEACHER -> siempre en la app, a cada profesor.
     * - recipient_type=STUDENT -> en la app, directo al alumno.
     * - si no -> comportamiento original, WhatsApp a cada tutor familiar.
     *
     * @param  array<string, mixed>  $data
     * @return Collection<int, AppNotification>
     */
    private function deliver(array $data, NotificationService $service, int $sentByUserId): Collection
    {
        $batchId = (string) Str::uuid();

        if (($data['target'] ?? null) === 'TEACHER') {
            return $this->resolveTargetTeachers($data)->map(
                fn (User $teacher) => $service->broadcastInApp($teacher, 'TEACHER', $data['type'], $data['title'], $data['message'], $sentByUserId, $batchId)
            )->values();
        }

        $students = $this->resolveTargetStudents($data);
        $recipientType = $data['recipient_type'] ?? 'TUTOR';

        if ($recipientType === 'STUDENT') {
            return $students->map(
                fn (User $student) => $service->broadcastInApp($student, 'STUDENT', $data['type'], $data['title'], $data['message'], $sentByUserId, $batchId)
            )->values();
        }

        return $students->flatMap(
            fn (User $student) => $service->broadcast($student, $data['type'], $data['title'], $data['message'], $sentByUserId, $batchId)
        )->values();
    }

    /**
     * Reenvia un aviso sin nuevo destino: recrea el envio hacia el mismo conjunto de
     * destinatarios originales (todo el batch, no solo el primero), agrupado bajo un
     * nuevo batch_id para que el reenvio aparezca como una sola entrada nueva en el log.
     *
     * @param  Collection<int, AppNotification>  $group
     * @return Collection<int, AppNotification>
     */
    private function resendToOriginalRecipients(Collection $group, NotificationService $service, int $sentByUserId): Collection
    {
        $first = $group->first();
        $batchId = (string) Str::uuid();
        $created = collect();

        $tutorStudentIds = $group->where('recipient_type', 'TUTOR')->pluck('student_id')->filter()->unique();
        $studentUserIds = $group->where('recipient_type', 'STUDENT')->pluck('user_id')->filter()->unique();
        $teacherUserIds = $group->where('recipient_type', 'TEACHER')->pluck('user_id')->filter()->unique();

        if ($tutorStudentIds->isNotEmpty()) {
            $created = $created->concat(
                User::whereKey($tutorStudentIds)->get()->flatMap(
                    fn (User $student) => $service->broadcast($student, $first->type, $first->title, $first->message, $sentByUserId, $batchId)
                )
            );
        }

        if ($studentUserIds->isNotEmpty()) {
            $created = $created->concat(
                User::whereKey($studentUserIds)->get()->map(
                    fn (User $student) => $service->broadcastInApp($student, 'STUDENT', $first->type, $first->title, $first->message, $sentByUserId, $batchId)
                )
            );
        }

        if ($teacherUserIds->isNotEmpty()) {
            $created = $created->concat(
                User::whereKey($teacherUserIds)->get()->map(
                    fn (User $teacher) => $service->broadcastInApp($teacher, 'TEACHER', $first->type, $first->title, $first->message, $sentByUserId, $batchId)
                )
            );
        }

        return $created->values();
    }

    /**
     * Anota en el modelo representativo el conteo y la lista de nombres de
     * destinatarios de su batch, para que el recurso los exponga sin cambiar de forma.
     *
     * @param  Collection<int, AppNotification>  $group
     */
    private function withRecipientSummary(Collection $group, ?AppNotification $representative = null): AppNotification
    {
        $representative ??= $group->first();

        $representative->setAttribute('recipients_count', $group->count());
        $representative->setAttribute('recipients', $group->map(fn (AppNotification $n) => match ($n->recipient_type) {
            'TEACHER' => $n->user?->fullName(),
            'TUTOR' => $n->tutor?->fullName(),
            default => $n->student?->fullName(),
        })->filter()->values()->all());

        return $representative;
    }

    /**
     * @return Collection<int, AppNotification>
     */
    private function batchSiblings(AppNotification $model): Collection
    {
        if ($model->batch_id === null) {
            return $model->newCollection([$model]);
        }

        return AppNotification::where('batch_id', $model->batch_id)->get();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return Collection<int, User>
     */
    private function resolveTargetStudents(array $data): Collection
    {
        $baseQuery = fn () => User::query()
            ->whereHas('role', fn ($query) => $query->where('name', 'alumno'))
            ->where('active', true);

        return match ($data['target'] ?? null) {
            'STUDENT', 'TUTOR' => $baseQuery()->whereKey($data['student_ids'])->get(),
            'GROUP' => $baseQuery()->whereIn('group_id', $data['group_ids'])->get(),
            'CAREER' => $baseQuery()->whereHas('group', fn ($query) => $query->whereIn('career_id', $data['career_ids']))->get(),
            'ALL' => $baseQuery()->get(),
            default => collect(),
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return Collection<int, User>
     */
    private function resolveTargetTeachers(array $data): Collection
    {
        return User::query()
            ->whereHas('role', fn ($query) => $query->whereIn('name', ['profesor', 'tutor_academico']))
            ->where('active', true)
            ->whereKey($data['teacher_ids'] ?? [])
            ->get();
    }

    private function findNotification(int $id): AppNotification
    {
        $notification = AppNotification::find($id);

        if ($notification === null) {
            throw ApiException::notFound('La notificación solicitada no existe.', 'NOT01');
        }

        return $notification;
    }
}
