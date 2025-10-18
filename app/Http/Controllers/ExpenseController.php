<?php

namespace App\Http\Controllers;

use App\Http\Requests\Expenses\CreateExpenseRequest;
use App\Http\Requests\Expenses\UpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Http\Resources\ExpenseCollection;
use App\Services\Expenses\Interfaces\ExpenseServiceInterface;
use App\Services\Expenses\DTO\CreateExpenseDTO;
use App\Services\Expenses\DTO\UpdateExpenseDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function __construct(
        private ExpenseServiceInterface $expenseService
    ) {}

    /**
     * Получить расходы группы
     */
    public function index(Request $request, string $groupId): ExpenseCollection
    {
        $user = $request->user();
        $expenses = $this->expenseService->getGroupExpenses($user, $groupId);

        return new ExpenseCollection($expenses);
    }

    /**
     * Создать расход в группе
     */
    public function store(CreateExpenseRequest $request, string $groupId): ExpenseResource
    {
        $user = $request->user();
        $validated = $request->validated();
        $validated['groupId'] = $groupId; // Добавляем groupId из URL
        
        $dto = CreateExpenseDTO::from($validated);
        $expense = $this->expenseService->createExpense($user, $dto);

        return new ExpenseResource($expense);
    }

    /**
     * Получить информацию о расходе
     */
    public function show(Request $request, string $groupId, string $expenseId): ExpenseResource
    {
        $user = $request->user();
        $expense = $this->expenseService->getExpense($user, $expenseId);

        return new ExpenseResource($expense);
    }

    /**
     * Обновить расход
     */
    public function update(UpdateExpenseRequest $request, string $groupId, string $expenseId): ExpenseResource
    {
        $user = $request->user();
        $dto = UpdateExpenseDTO::from($request->validated());
        $expense = $this->expenseService->updateExpense($user, $expenseId, $dto);

        return new ExpenseResource($expense);
    }

    /**
     * Удалить расход
     */
    public function destroy(Request $request, string $groupId, string $expenseId): JsonResponse
    {
        $user = $request->user();
        $this->expenseService->deleteExpense($user, $expenseId);

        return response()->json([
            'success' => true,
            'message' => 'Expense deleted successfully',
        ]);
    }

    /**
     * Получить расходы пользователя (из всех групп)
     */
    public function userExpenses(Request $request): ExpenseCollection
    {
        $user = $request->user();
        $expenses = $this->expenseService->getUserExpenses($user);

        return new ExpenseCollection($expenses);
    }
}