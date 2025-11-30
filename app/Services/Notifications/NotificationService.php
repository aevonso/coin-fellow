<?php

namespace App\Services\Notifications;

use App\Events\NotificationCreated;
use App\Models\Notification;
use App\Models\User;
use App\Services\Notifications\DTO\CreateNotificationDTO;
use App\Services\Notifications\Interfaces\NotificationServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class NotificationService implements NotificationServiceInterface
{

    const CACHE_UNREAD_COUNT = 'user_notifications_unread_count:';
    const CACHE_TTL = 3600; // 1 час

    public function createNotification(CreateNotificationDTO $dto): Notification
    {
        return DB::transaction(function () use ($dto) {
            $notification = Notification::create([
                'user_id' => $dto->userId,
                'group_id' => $dto->groupId,
                'type' => $dto->type,
                'message' => $dto->message,
                'data' => $dto->data,
                'is_read' => false,
            ]);


            $this->invalidateUnreadCountCache($dto->userId);

            $this->broadcastNotification($notification);

            return $notification;
        });
    }

    public function getUserNotifications(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return Notification::forUser($user->id)
            ->with(['group'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getUnreadCount(User $user): int
    {
        return Cache::remember(
            self::CACHE_UNREAD_COUNT . $user->id,
            self::CACHE_TTL,
            function () use ($user) {
                return Notification::forUser($user->id)
                    ->unread()
                    ->count();
            }
        );
    }

    public function markAsRead(User $user, string $notificationId): void
    {
        $notification = Notification::forUser($user->id)
            ->findOrFail($notificationId);

        if ($notification->isUnread()) {
            $notification->markAsRead();
            $this->invalidateUnreadCountCache($user->id);
        }
    }

    public function markAllAsRead(User $user): void
    {
        $updated = Notification::forUser($user->id)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);

        if ($updated > 0) {
            $this->invalidateUnreadCountCache($user->id);
        }
    }

    public function deleteNotification(User $user, string $notificationId): void
    {
        $notification = Notification::forUser($user->id)
            ->findOrFail($notificationId);

        $wasUnread = $notification->isUnread();
        $notification->delete();

        if ($wasUnread) {
            $this->invalidateUnreadCountCache($user->id);
        }
    }



    public function notifyNewExpense(User $user, array $expenseData): void
    {
        $dto = new CreateNotificationDTO(
            userId: $user->id,
            type: Notification::TYPE_NEW_EXPENSE,
            message: "Новый расход: {$expenseData['description']} на {$expenseData['amount']}₽",
            groupId: $expenseData['group_id'],
            data: $expenseData
        );


        dispatch(fn () => $this->createNotification($dto));
    }

    public function notifyPaymentRequest(User $user, array $paymentData): void
    {
        $dto = new CreateNotificationDTO(
            userId: $paymentData['to_user_id'],
            type: Notification::TYPE_PAYMENT_REQUEST,
            message: "Запрос на оплату: {$paymentData['amount']}₽ от {$paymentData['from_user_name']}",
            groupId: $paymentData['group_id'],
            data: $paymentData
        );

        dispatch(fn () => $this->createNotification($dto));
    }

    public function notifyPaymentConfirmed(User $user, array $paymentData): void
    {
        $dto = new CreateNotificationDTO(
            userId: $paymentData['from_user_id'],
            type: Notification::TYPE_PAYMENT_CONFIRMED,
            message: "Платеж подтвержден: {$paymentData['amount']}₽ получено от {$paymentData['to_user_name']}",
            groupId: $paymentData['group_id'],
            data: $paymentData
        );

        dispatch(fn () => $this->createNotification($dto));
    }

    public function notifyInvitation(User $user, array $invitationData): void
    {
        $dto = new CreateNotificationDTO(
            userId: $invitationData['invitee_id'],
            type: Notification::TYPE_INVITATION,
            message: "Приглашение в группу: {$invitationData['group_name']}",
            groupId: $invitationData['group_id'],
            data: $invitationData
        );

        dispatch(fn () => $this->createNotification($dto));
    }



    public function broadcastNotification(Notification $notification): void
    {
      
        event(new NotificationCreated($notification));
    }



    public function cleanupOldNotifications(int $days = 30): void
    {
        $cutoffDate = now()->subDays($days);
        
        Notification::where('created_at', '<', $cutoffDate)
            ->where('is_read', true)
            ->chunkById(1000, function ($notifications) {
                $userIds = $notifications->pluck('user_id')->unique();
                
                Notification::whereIn('id', $notifications->pluck('id'))->delete();
                
        
                foreach ($userIds as $userId) {
                    $this->invalidateUnreadCountCache($userId);
                }
            });
    }

   

    private function invalidateUnreadCountCache(string $userId): void
    {
        Cache::forget(self::CACHE_UNREAD_COUNT . $userId);
    }
}