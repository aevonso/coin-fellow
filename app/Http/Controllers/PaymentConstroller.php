<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payments\CreatePaymentRequest;
use App\Http\Requests\Payments\UpdatePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Http\Resources\Collections\PaymentCollection;
use App\Services\Payments\DTO\CreatePaymentDTO;
use App\Services\Payments\DTO\UpdatePaymentDTO;
use App\Services\Payments\Interfaces\PaymentServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentServiceInterface $paymentService
    ) {}

    public function getGroupPayments(Request $request, string $groupId): PaymentCollection
    {
        $user = $request->user();
        $payments = $this->paymentService->getGroupPayments($user, $groupId);

        return new PaymentCollection($payments);
    }


    public function createPayment(CreatePaymentRequest $request, string $groupId): PaymentResource
    {
        $user = $request->user();
        $validated = $request->validated();
        $validated['groupId'] = $groupId;
        
        $dto = CreatePaymentDTO::from($validated);
        $payment = $this->paymentService->createPayment($user, $dto);

        return new PaymentResource($payment);
    }

    public function getPayment(Request $request, string $groupId, string $paymentId): PaymentResource
    {
        $user = $request->user();
        $payment = $this->paymentService->getPayment($user, $paymentId);

        return new PaymentResource($payment);
    }


    public function updatePayment(UpdatePaymentRequest $request, string $groupId, string $paymentId): PaymentResource
    {
        $user = $request->user();
        $dto = UpdatePaymentDTO::from($request->validated());
        $payment = $this->paymentService->updatePayment($user, $paymentId, $dto);

        return new PaymentResource($payment);
    }


    public function deletePayment(Request $request, string $groupId, string $paymentId): JsonResponse
    {
        $user = $request->user();
        $this->paymentService->deletePayment($user, $paymentId);

        return response()->json([
            'success' => true,
            'message' => 'Платеж успешно удален',
        ]);
    }


    public function confirmPayment(Request $request, string $groupId, string $paymentId): PaymentResource
    {
        $user = $request->user();
        $payment = $this->paymentService->confirmPayment($user, $paymentId);

        return new PaymentResource($payment);
    }

    public function rejectPayment(Request $request, string $groupId, string $paymentId): PaymentResource
    {
        $user = $request->user();
        $payment = $this->paymentService->rejectPayment($user, $paymentId);

        return new PaymentResource($payment);
    }

    public function getUserPayments(Request $request): PaymentCollection
    {
        $user = $request->user();
        $payments = $this->paymentService->getUserPayments($user);

        return new PaymentCollection($payments);
    }


    public function getPaymentStatistics(Request $request, string $groupId): JsonResponse
    {
        $user = $request->user();
        $statistics = $this->paymentService->getPaymentStatistics($user, $groupId);

        return response()->json([
            'success' => true,
            'data' => $statistics,
            'message' => 'Успешно восстановлена статистика платежей'
        ]);
    }

    public function getPendingPayments(Request $request, string $groupId): JsonResponse
    {
        $user = $request->user();
        $payments = $this->paymentService->getPendingPayments($user, $groupId);

        return response()->json([
            'success' => true,
            'data' => PaymentResource::collection($payments),
            'message' => 'Ожидающие выплаты успешно получены'
        ]);
    }
}