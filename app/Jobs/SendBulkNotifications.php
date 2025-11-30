<?php

namespace App\Jobs;

use App\Services\Notifications\Interfaces\NotificationServiceInterface;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBulkNotifications implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, SerializesModels;

    public function __construct(
        private array $userIds,
        private string $type,
        private string $message,
        private ?string $groupId = null,
        private ?array $data = null
    ) {}

    public function handle(NotificationServiceInterface $notificationService): void
    {
        if ($this->batch() && $this->batch()->cancelled()) {
            return;
        }

        foreach ($this->userIds as $userId) {
            $notificationService->createNotification(
                new \App\Services\Notifications\DTO\CreateNotificationDTO(
                    userId: $userId,
                    type: $this->type,
                    message: $this->message,
                    groupId: $this->groupId,
                    data: $this->data
                )
            );
        }
    }
}