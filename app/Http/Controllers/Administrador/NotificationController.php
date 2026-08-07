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

class NotificationController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $notifications = AppNotification::query()
            ->with(['student', 'tutor'])
            ->when($request->query('type'), fn ($query, $type) => $query->where('type', $type))
            ->when($request->has('is_read'), fn ($query) => $query->where('is_read', $request->boolean('is_read')))
            ->when($request->query('date_from'), fn ($query, $date) => $query->whereDate('sent_at', '>=', $date))
            ->when($request->query('date_to'), fn ($query, $date) => $query->whereDate('sent_at', '<=', $date))
            ->latest('sent_at')
            ->get();

        return $this->successResponse('Notificaciones obtenidas correctamente.', AdminNotificationSummaryResource::collection($notifications));
    }

    public function show(int $notification): JsonResponse
    {
        $model = $this->findNotification($notification)->load(['student', 'tutor', 'sentBy']);

        return $this->successResponse('Notificación obtenida correctamente.', new AdminNotificationResource($model));
    }

    public function store(StoreNotificationRequest $request, NotificationService $service): JsonResponse
    {
        $data = $request->validated();

        $students = $this->resolveTargetStudents($data);

        if ($students->isEmpty()) {
            throw ApiException::unprocessable('Debes indicar al menos un destinatario válido.', 'NOT02');
        }

        $created = $students->flatMap(
            fn (User $student) => $service->broadcast($student, $data['type'], $data['title'], $data['message'], $request->user()->id)
        );

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

        $students = isset($data['target'])
            ? $this->resolveTargetStudents($data)
            : collect([$original->student]);

        if ($students->isEmpty()) {
            throw ApiException::unprocessable('Debes indicar al menos un destinatario válido.', 'NOT02');
        }

        $created = $students->flatMap(
            fn (User $student) => $service->broadcast($student, $original->type, $original->title, $original->message, $request->user()->id)
        );

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

    private function findNotification(int $id): AppNotification
    {
        $notification = AppNotification::find($id);

        if ($notification === null) {
            throw ApiException::notFound('La notificación solicitada no existe.', 'NOT01');
        }

        return $notification;
    }
}
