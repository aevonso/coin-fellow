<?php

namespace App\Services\Notifications\Interfaces;

use App\Models\Notification;
use App\Models\User;
use App\Services\Notifications\DTO\CreateNotificationDTO;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface NotificationServiceInterface
{
 
    public function createNotification(CreateNotificationDTO $dto): Notification;
    public function getUserNotifications(User $user, int $perPage = 20): LengthAwarePaginator;
    public function getUnreadCount(User $user): int;
    public function markAsRead(User $user, string $notificationId): void;
    public function markAllAsRead(User $user): void;
    public function deleteNotification(User $user, string $notificationId): void;
    

    public function notifyNewExpense(User $user, array $expenseData): void;
    public function notifyPaymentRequest(User $user, array $paymentData): void;
    public function notifyPaymentConfirmed(User $user, array $paymentData): void;
    public function notifyInvitation(User $user, array $invitationData): void;
    

    public function broadcastNotification(Notification $notification): void;
    
 
    public function cleanupOldNotifications(int $days = 30): void;
}