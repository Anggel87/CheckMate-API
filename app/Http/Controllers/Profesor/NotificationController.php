<?php

namespace App\Http\Controllers\Profesor;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\RecipientNotificationResource;
use App\Models\AppNotification;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $notifications = AppNotification::query()
            ->where('user_id', $request->user()->id)
            ->where('recipient_type', 'TEACHER')
            ->with('sentBy')
            ->latest('sent_at')
            ->get();

        return $this->successResponse('Notificaciones obtenidas correctamente.', RecipientNotificationResource::collection($notifications));
    }

    public function show(Request $request, int $notification): JsonResponse
    {
        $model = $this->findOwnNotification($request->user()->id, $notification)->load('sentBy');

        return $this->successResponse('Notificación obtenida correctamente.', new RecipientNotificationResource($model));
    }

    public function markRead(Request $request, int $notification): JsonResponse
    {
        $model = $this->findOwnNotification($request->user()->id, $notification);
        $model->update(['is_read' => true]);

        return $this->successResponse('Notificación marcada como leída.', new RecipientNotificationResource($model));
    }

    private function findOwnNotification(int $userId, int $id): AppNotification
    {
        $notification = AppNotification::where('user_id', $userId)
            ->where('recipient_type', 'TEACHER')
            ->find($id);

        if ($notification === null) {
            throw ApiException::notFound('La notificación solicitada no existe.', 'NOT01');
        }

        return $notification;
    }
}
