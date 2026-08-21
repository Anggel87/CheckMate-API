<?php

namespace App\Http\Controllers\Director;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Director\StoreNotificationRequest;
use App\Http\Resources\AdminNotificationResource;
use App\Http\Resources\AdminNotificationSummaryResource;
use App\Models\AppNotification;
use App\Models\User;
use App\Services\Director\CareerScope;
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
     * Un director solo ve los avisos que el mismo envio (igual que el panel de
     * administrador es tambien un log de lo que ese usuario ha enviado). Un envio a N
     * destinatarios crea una fila por destinatario (comparten batch_id), pero el log
     * solo debe mostrar una entrada por envio.
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = AppNotification::query()
            ->where('sent_by_user_id', $request->user()->id)
            ->when($request->query('type'), fn ($query, $type) => $query->where('type', $type))
            ->when($request->has('is_read'), fn ($query) => $query->where('is_read', $request->boolean('is_read')))
            ->latest('sent_at')
            ->get()
            ->groupBy(fn (AppNotification $n) => $n->batch_id ?? (string) $n->id)
            ->map(fn (Collection $group) => $this->withRecipientSummary($group))
            ->values();

        return $this->successResponse('Notificaciones obtenidas correctamente.', AdminNotificationSummaryResource::collection($notifications));
    }

    public function show(Request $request, int $notification): JsonResponse
    {
        $model = $this->findOwnNotification($request->user(), $notification)
            ->load(['student', 'tutor', 'user', 'sentBy']);
        $group = $this->batchSiblings($model)->load(['student', 'tutor', 'user']);

        return $this->successResponse('Notificación obtenida correctamente.', new AdminNotificationResource($this->withRecipientSummary($group, $model)));
    }

    /**
     * Marca como leida toda la fila (o el batch completo, si el aviso se envio a
     * varios destinatarios) para que el estado se mantenga consistente sin importar
     * cual fila del batch quede como representante la proxima vez que se liste.
     */
    public function markRead(Request $request, int $notification): JsonResponse
    {
        $model = $this->findOwnNotification($request->user(), $notification);

        if ($model->batch_id !== null) {
            AppNotification::where('batch_id', $model->batch_id)->update(['is_read' => true]);
        } else {
            $model->update(['is_read' => true]);
        }

        return $this->successResponse('Notificación marcada como leída.', new AdminNotificationSummaryResource($model->fresh()));
    }

    public function store(StoreNotificationRequest $request, NotificationService $service, CareerScope $scope): JsonResponse
    {
        $director = $request->user();
        $careerIds = $scope->assertHasCareer($director);
        $data = $request->validated();

        $created = $this->deliver($data, $service, $director, $scope, $careerIds);

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

    /**
     * @param  array<string, mixed>  $data
     * @param  Collection<int, int>  $careerIds
     * @return Collection<int, AppNotification>
     */
    private function deliver(array $data, NotificationService $service, User $director, CareerScope $scope, Collection $careerIds): Collection
    {
        $batchId = (string) Str::uuid();

        if ($data['target'] === 'TEACHER') {
            $allowed = $scope->teacherIds($careerIds);
            $teacherIds = $this->assertWithinScope($data['teacher_ids'], $allowed);
            $teachers = User::query()->whereKey($teacherIds)->where('active', true)->get();

            return $teachers->map(
                fn (User $teacher) => $service->broadcastInApp($teacher, 'TEACHER', $data['type'], $data['title'], $data['message'], $director->id, $batchId)
            )->values();
        }

        $students = $this->resolveTargetStudents($data, $scope, $careerIds);
        $recipientType = $data['recipient_type'] ?? 'TUTOR';

        if ($recipientType === 'STUDENT') {
            return $students->map(
                fn (User $student) => $service->broadcastInApp($student, 'STUDENT', $data['type'], $data['title'], $data['message'], $director->id, $batchId)
            )->values();
        }

        return $students->flatMap(
            fn (User $student) => $service->broadcast($student, $data['type'], $data['title'], $data['message'], $director->id, $batchId)
        )->values();
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
     * @param  Collection<int, int>  $careerIds
     * @return Collection<int, User>
     */
    private function resolveTargetStudents(array $data, CareerScope $scope, Collection $careerIds): Collection
    {
        $baseQuery = fn () => User::query()
            ->whereHas('role', fn ($query) => $query->where('name', 'alumno'))
            ->where('active', true);

        return match ($data['target']) {
            'STUDENT', 'TUTOR' => $baseQuery()->whereKey(
                $this->assertWithinScope($data['student_ids'], $scope->studentIds($careerIds))
            )->get(),
            'GROUP' => $baseQuery()->whereIn('group_id',
                $this->assertWithinScope($data['group_ids'], $scope->groupIds($careerIds))
            )->get(),
            'CAREER' => $baseQuery()->whereIn('group_id', $scope->groupIds($careerIds))->get(),
            default => collect(),
        };
    }

    /**
     * Un director solo puede dirigirse a ids dentro de su propia carrera — si algun id
     * enviado no esta en el conjunto permitido, se rechaza la peticion completa en vez
     * de silenciosamente descartarlo (mismo criterio de rechazo ruidoso que el resto de
     * los controladores de este namespace, ej. StudentController::findStudent).
     *
     * @param  array<int, int>  $submitted
     * @param  Collection<int, int>  $allowed
     * @return array<int, int>
     */
    private function assertWithinScope(array $submitted, Collection $allowed): array
    {
        $outsideScope = collect($submitted)->diff($allowed);

        if ($outsideScope->isNotEmpty()) {
            throw ApiException::forbidden('No tienes acceso a este recurso.', 'PERM01');
        }

        return $submitted;
    }

    private function findOwnNotification(User $director, int $id): AppNotification
    {
        $notification = AppNotification::where('sent_by_user_id', $director->id)->find($id);

        if ($notification === null) {
            throw ApiException::notFound('La notificación solicitada no existe.', 'NOT01');
        }

        return $notification;
    }
}
