<?php

namespace App\Services\Payments\Interfaces;

use App\Models\Payment;
use App\Models\User;
use App\Services\Payments\DTO\CreatePaymentDTO;
use App\Services\Payments\DTO\UpdatePaymentDTO;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface PaymentServiceInterface {
    //crud
    public function getGroupPayments(User $user, string $groupId): LenghtAwarePaginator;
    public function createPayment(User $user, CreatePaymentDTO $dto): Payment;
    public function getPayment(User $user, string $paymentId): Payment;
    public function updatePayment(User $user, string $paymentId, UpdatePaymentDTO $dto): Payment;
    public function deletePayment(User $user, string $paymentId): void;

    //pay
    public function confirmPayment(User $user, string $paymentId): Payment;
    public function rejectPayment(User $user, string $paymentId): Payment;
    public function getUserPayments(User $user): LenghtAwarePaginator;

    //statistic and analitic
    public function getPaymentStatistics(User $user, string $groupId):array;
    public function getPendingPayments(User $user, string $groupId): Collection;

    //integration on balances
    public function settlePayment(User $user, string $paymentId): void;
}
