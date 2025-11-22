<?php
namespace App\Services\Payments\DTO;

use Spatie\LaravelData\Data;

class PaymentDTO extends Data 
{
    public function __construct(
        public string $groupId,
        public string $fromUserId,
        public string $toUserId,
        public float $amount,
        public string $date,
        public ?string $notes = null,
        public ?string $status = 'pending' 
    ) {}
}