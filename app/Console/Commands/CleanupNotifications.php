<?php

namespace App\Console\Commands;

use App\Services\Notifications\Interfaces\NotificationServiceInterface;
use Illuminate\Console\Command;

class CleanupNotifications extends Command
{
    protected $signature = 'notifications:cleanup {--days=30 : Older than days}';
    protected $description = 'Clean up old read notifications';

    public function handle(NotificationServiceInterface $notificationService): void
    {
        $days = $this->option('days');
        
        $this->info("Очистка уведомлений займет {$days} дней..");
        
        $notificationService->cleanupOldNotifications($days);
        
        $this->info('Очистка уведомлений успешна');
    }
}