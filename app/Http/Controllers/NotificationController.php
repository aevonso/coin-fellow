<?php

namespace App\Http\Controllers;

use App\Http\Requests\Notifications\MarkAsReadRequest;
use App\Http\Resources\Collections\NotificationCollection;
use App\Http\Resources\NotificationResource;
use App\Services\Notifications\Interfaces\NotificationServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(
        private NotificationServiceInterface $notificationService
    ) {}

    public function index(Request $request): NotificationCollection
    {
        $user = $request->user();
        $perPage = $request->get('per_page', 20);
        
        $notifications = $this->notificationService->getUserNotifications($user, $perPage);

        return new NotificationCollection($notifications);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user();
        $count = $this->notificationService->getUnreadCount($user);

        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => $count,
            ],
            'message' => 'Количество непрочитанных сообщений успешно восстановлено',
        ]);
    }


    public function markAsRead(Request $request, string $notificationId): JsonResponse
    {
        $user = $request->user();
        $this->notificationService->markAsRead($user, $notificationId);

        return response()->json([
            'success' => true,
            'message' => 'Уведомление помечено как прочитанное',
        ]);
    }


    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->notificationService->markAllAsRead($user);

        return response()->json([
            'success' => true,
            'message' => 'Все уведомления помечены как прочитанные',
        ]);
    }


    public function markMultipleAsRead(MarkAsReadRequest $request): JsonResponse
    {
        $user = $request->user();
        $notificationIds = $request->input('notification_ids', []);

        foreach ($notificationIds as $notificationId) {
            $this->notificationService->markAsRead($user, $notificationId);
        }

        return response()->json([
            'success' => true,
            'message' => 'Все уведомления помечены как прочитанные',
        ]);
    }

    public function destroy(Request $request, string $notificationId): JsonResponse
    {
        $user = $request->user();
        $this->notificationService->deleteNotification($user, $notificationId);

        return response()->json([
            'success' => true,
            'message' => 'Уведомления успешно удалены',
        ]);
    }

 
    public function recent(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = min($request->get('limit', 10), 50); 
        
        $notifications = $this->notificationService->getUserNotifications($user, $limit);

        return response()->json([
            'success' => true,
            'data' => NotificationResource::collection($notifications),
            'message' => 'Успешно получены последние уведомления',
        ]);
    }
}