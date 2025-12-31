<?php

namespace App\Services\RecurringExpenses;

use App\Models\RecurringExpense;
use App\Models\Expense;
use App\Models\User;
use App\Models\Group;
use App\Services\Expenses\Interfaces\ExpenseServiceInterface;
use App\Services\Notifications\Interfaces\NotificationServiceInterface;
use App\Services\RecurringExpenses\DTO\CreateRecurringExpenseDTO;
use App\Services\RecurringExpenses\DTO\UpdateRecurringExpenseDTO;
use App\Services\RecurringExpenses\DTO\GeneratedExpenseDTO;
use App\Services\RecurringExpenses\Interfaces\RecurringExpenseServiceInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecurringExpenseService implements RecurringExpenseServiceInterface
{
    public function __construct(
        private ExpenseServiceInterface $expenseService,
        private NotificationServiceInterface $notificationService
    ) {}

    public function getGroupRecurringExpenses(User $user, string $groupId): LengthAwarePaginator
    {
        $group = Group::findOrFail($groupId);

        if (!$group->users->contains($user->id)) {
            throw ValidationException::withMessages([
                'group' => ['Вы не являетесь участником этой группы'],
            ]);
        }

        return RecurringExpense::forGroup($groupId)
            ->with(['payer', 'category', 'group'])
            ->orderBy('is_active', 'desc')
            ->orderBy('next_occurrence')
            ->paginate(20);
    }

    public function createRecurringExpense(User $user, CreateRecurringExpenseDTO $dto): RecurringExpense
    {
        $group = Group::findOrFail($dto->groupId);

        if (!$group->users->contains($user->id)) {
            throw ValidationException::withMessages([
                'group' => ['Вы не являетесь участником этой группы'],
            ]);
        }

        if ($user->id !== $dto->payerId) {
            throw ValidationException::withMessages([
                'payer_id' => ['Вы можете создавать повторяющиеся расходы только от своего имени'],
            ]);
        }

        $this->validateSchedule($dto);

        return DB::transaction(function () use ($dto, $user) {
            $nextOccurrence = $this->calculateInitialNextOccurrence($dto);

            $recurringExpense = RecurringExpense::create([
                'group_id' => $dto->groupId,
                'payer_id' => $dto->payerId,
                'category_id' => $dto->categoryId,
                'description' => $dto->description,
                'amount' => $dto->amount,
                'frequency' => $dto->frequency,
                'start_date' => $dto->startDate,
                'end_date' => $dto->endDate,
                'day_of_month' => $dto->dayOfMonth,
                'weekdays' => $dto->weekdays,
                'next_occurrence' => $nextOccurrence,
                'is_active' => true,
                'last_generated_at' => null,
            ]);

            $this->notifyRecurringExpenseCreated($recurringExpense, $user);

            return $recurringExpense->load(['payer', 'category', 'group']);
        });
    }

    public function getRecurringExpense(User $user, string $recurringExpenseId): RecurringExpense
    {
        $recurringExpense = RecurringExpense::with(['payer', 'category', 'group.users'])
            ->findOrFail($recurringExpenseId);

        if (!$recurringExpense->group->users->contains($user->id)) {
            throw ValidationException::withMessages([
                'recurring_expense' => ['Вы не являетесь участником этой группы'],
            ]);
        }

        return $recurringExpense;
    }

    public function updateRecurringExpense(User $user, string $recurringExpenseId, UpdateRecurringExpenseDTO $dto): RecurringExpense
    {
        $recurringExpense = RecurringExpense::with(['group'])->findOrFail($recurringExpenseId);

        $this->checkRecurringExpensePermissions($user, $recurringExpense);

        if ($dto->description !== null) {
            $recurringExpense->description = $dto->description;
        }
        if ($dto->amount !== null) {
            $recurringExpense->amount = $dto->amount;
        }
        if ($dto->frequency !== null) {
            $recurringExpense->frequency = $dto->frequency;
        }
        if ($dto->startDate !== null) {
            $recurringExpense->start_date = $dto->startDate;
        }
        if ($dto->endDate !== null) {
            $recurringExpense->end_date = $dto->endDate;
        }
        if ($dto->categoryId !== null) {
            $recurringExpense->category_id = $dto->categoryId;
        }
        if ($dto->dayOfMonth !== null) {
            $recurringExpense->day_of_month = $dto->dayOfMonth;
        }
        if ($dto->weekdays !== null) {
            $recurringExpense->weekdays = $dto->weekdays;
        }
        if ($dto->isActive !== null) {
            $recurringExpense->is_active = $dto->isActive;
        }

        if ($dto->frequency !== null || $dto->startDate !== null || 
            $dto->dayOfMonth !== null || $dto->weekdays !== null) {
            $recurringExpense->next_occurrence = $recurringExpense->calculateNextOccurrence();
        }

        $recurringExpense->save();

        if ($dto->isActive !== null && $dto->isActive === true) {
            $this->notifyRecurringExpenseActivated($recurringExpense, $user);
        }

        return $recurringExpense->load(['payer', 'category', 'group']);
    }

    public function deleteRecurringExpense(User $user, string $recurringExpenseId): void
    {
        $recurringExpense = RecurringExpense::with(['group'])->findOrFail($recurringExpenseId);

        $this->checkRecurringExpensePermissions($user, $recurringExpense);

        $recurringExpense->delete();

        $this->notifyRecurringExpenseDeleted($recurringExpense, $user);
    }

    public function toggleRecurringExpense(User $user, string $recurringExpenseId, bool $isActive): RecurringExpense
    {
        $recurringExpense = RecurringExpense::with(['group'])->findOrFail($recurringExpenseId);

        $this->checkRecurringExpensePermissions($user, $recurringExpense);

        $recurringExpense->is_active = $isActive;
        
        if ($isActive && !$recurringExpense->next_occurrence) {
            $recurringExpense->next_occurrence = $recurringExpense->calculateNextOccurrence();
        }
        
        $recurringExpense->save();

        if ($isActive) {
            $this->notifyRecurringExpenseActivated($recurringExpense, $user);
        } else {
            $this->notifyRecurringExpenseDeactivated($recurringExpense, $user);
        }

        return $recurringExpense->load(['payer', 'category', 'group']);
    }

    public function getUserRecurringExpenses(User $user): LengthAwarePaginator
    {
        return RecurringExpense::where('payer_id', $user->id)
            ->orWhereHas('group.users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['payer', 'category', 'group'])
            ->orderBy('is_active', 'desc')
            ->orderBy('next_occurrence')
            ->paginate(20);
    }

    public function getUpcomingRecurringExpenses(User $user, string $groupId): Collection
    {
        $group = Group::findOrFail($groupId);

        if (!$group->users->contains($user->id)) {
            throw ValidationException::withMessages([
                'group' => ['Вы не являетесь участником этой группы'],
            ]);
        }

        return RecurringExpense::forGroup($groupId)
            ->active()
            ->where('next_occurrence', '>=', now()->toDateString())
            ->with(['payer', 'category'])
            ->orderBy('next_occurrence')
            ->limit(10)
            ->get()
            ->map(function ($recurringExpense) {
                return [
                    'id' => $recurringExpense->id,
                    'description' => $recurringExpense->description,
                    'amount' => (float) $recurringExpense->amount,
                    'next_occurrence' => $recurringExpense->next_occurrence,
                    'schedule_description' => $recurringExpense->getScheduleDescription(),
                    'payer' => $recurringExpense->payer->first_name ?? $recurringExpense->payer->username,
                    'category' => $recurringExpense->category?->name
                ];
            });
    }

    public function getRecurringExpenseHistory(User $user, string $recurringExpenseId): Collection
    {
        $recurringExpense = $this->getRecurringExpense($user, $recurringExpenseId);

        return Expense::where('recurring_expense_id', $recurringExpenseId)
            ->with(['category', 'payer'])
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($expense) {
                return [
                    'id' => $expense->id,
                    'description' => $expense->description,
                    'amount' => (float) $expense->amount,
                    'date' => $expense->date->format('Y-m-d'),
                    'category' => $expense->category?->name,
                    'payer' => $expense->payer->first_name ?? $expense->payer->username,
                    'created_at' => $expense->created_at
                ];
            });
    }

    public function generateDueRecurringExpenses(): int
    {
        $generatedCount = 0;

        RecurringExpense::dueForGeneration()
            ->with(['group.users'])
            ->chunk(50, function ($recurringExpenses) use (&$generatedCount) {
                foreach ($recurringExpenses as $recurringExpense) {
                    try {
                        $this->generateExpenseFromRecurring($recurringExpense);
                        $generatedCount++;
                    } catch (\Exception $e) {
                        \Log::error('Failed to generate recurring expense', [
                            'recurring_expense_id' => $recurringExpense->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            });

        return $generatedCount;
    }

    public function previewNextOccurrences(string $recurringExpenseId, int $count = 5): array
    {
        $recurringExpense = RecurringExpense::findOrFail($recurringExpenseId);
        
        $occurrences = [];
        $currentDate = $recurringExpense->next_occurrence ?? $recurringExpense->start_date;
        
        for ($i = 0; $i < $count; $i++) {
            $occurrences[] = [
                'date' => $currentDate,
                'weekday' => date('l', strtotime($currentDate)),
                'is_weekend' => in_array(date('N', strtotime($currentDate)), [6, 7])
            ];
            
            $nextDate = $recurringExpense->calculateNextOccurrence();
            if (!$nextDate) break;
            
            $recurringExpense->next_occurrence = $nextDate;
            $currentDate = $nextDate;
        }
        
        return $occurrences;
    }

    public function skipNextOccurrence(User $user, string $recurringExpenseId): RecurringExpense
    {
        $recurringExpense = RecurringExpense::with(['group'])->findOrFail($recurringExpenseId);

        $this->checkRecurringExpensePermissions($user, $recurringExpense);

        $nextOccurrence = $recurringExpense->calculateNextOccurrence();
        if ($nextOccurrence) {
            $recurringExpense->next_occurrence = $nextOccurrence;
            $recurringExpense->save();
        }

        $this->notifyRecurringExpenseSkipped($recurringExpense, $user);

        return $recurringExpense->load(['payer', 'category', 'group']);
    }

    public function generateNextOccurrence(User $user, string $recurringExpenseId): void
    {
        $recurringExpense = RecurringExpense::with(['group.users'])->findOrFail($recurringExpenseId);

        $this->checkRecurringExpensePermissions($user, $recurringExpense);

        $this->generateExpenseFromRecurring($recurringExpense);
    }

    private function generateExpenseFromRecurring(RecurringExpense $recurringExpense): void
    {
        DB::transaction(function () use ($recurringExpense) {
            $expenseDTO = new GeneratedExpenseDTO(
                recurringExpenseId: $recurringExpense->id,
                description: $recurringExpense->description,
                amount: (float) $recurringExpense->amount,
                date: $recurringExpense->next_occurrence,
                groupId: $recurringExpense->group_id,
                payerId: $recurringExpense->payer_id,
                categoryId: $recurringExpense->category_id
            );

            $this->createExpenseFromRecurring($expenseDTO, $recurringExpense);

            $recurringExpense->last_generated_at = now();
            $recurringExpense->next_occurrence = $recurringExpense->calculateNextOccurrence();
            $recurringExpense->save();

            $this->notifyRecurringExpenseGenerated($recurringExpense);
        });
    }

    private function createExpenseFromRecurring(GeneratedExpenseDTO $dto, RecurringExpense $recurringExpense): void
    {
        $expenseData = [
            'description' => $dto->description,
            'amount' => $dto->amount,
            'date' => $dto->date,
            'groupId' => $dto->groupId,
            'categoryId' => $dto->categoryId,
            'participants' => $recurringExpense->group->users->pluck('id')->toArray()
        ];

        $user = User::find($dto->payerId);
        $createExpenseDTO = \App\Services\Expenses\DTO\CreateExpenseDTO::from($expenseData);
        
        $this->expenseService->createExpense($user, $createExpenseDTO);
    }

    private function validateSchedule(CreateRecurringExpenseDTO $dto): void
    {
        if ($dto->frequency === RecurringExpense::FREQUENCY_MONTHLY && !$dto->dayOfMonth) {
            throw ValidationException::withMessages([
                'day_of_month' => ['Для ежемесячного расписания необходимо указать день месяца'],
            ]);
        }

        if ($dto->frequency === RecurringExpense::FREQUENCY_WEEKLY && empty($dto->weekdays)) {
            throw ValidationException::withMessages([
                'weekdays' => ['Для еженедельного расписания необходимо указать дни недели'],
            ]);
        }

        if ($dto->endDate && $dto->endDate <= $dto->startDate) {
            throw ValidationException::withMessages([
                'end_date' => ['Дата окончания должна быть после даты начала'],
            ]);
        }

        if ($dto->dayOfMonth && ($dto->dayOfMonth < 1 || $dto->dayOfMonth > 31)) {
            throw ValidationException::withMessages([
                'day_of_month' => ['День месяца должен быть от 1 до 31'],
            ]);
        }
    }

    private function calculateInitialNextOccurrence(CreateRecurringExpenseDTO $dto): string
    {
        $recurringExpense = new RecurringExpense([
            'frequency' => $dto->frequency,
            'start_date' => $dto->startDate,
            'day_of_month' => $dto->dayOfMonth,
            'weekdays' => $dto->weekdays,
            'next_occurrence' => $dto->startDate
        ]);

        return $recurringExpense->calculateNextOccurrence() ?? $dto->startDate;
    }

    private function checkRecurringExpensePermissions(User $user, RecurringExpense $recurringExpense): void
    {
        $isPayer = $recurringExpense->payer_id === $user->id;
        $isGroupAdmin = $recurringExpense->group->isUserAdmin($user);

        if (!$isPayer && !$isGroupAdmin) {
            throw ValidationException::withMessages([
                'permission' => ['У вас нет разрешения на изменение этого повторяющегося расхода'],
            ]);
        }
    }

    private function notifyRecurringExpenseCreated(RecurringExpense $recurringExpense, User $creator): void
    {
        $participants = $recurringExpense->group->users->where('id', '!=', $creator->id);
        
        foreach ($participants as $participant) {
            $this->notificationService->createNotification(
                new \App\Services\Notifications\DTO\CreateNotificationDTO(
                    userId: $participant->id,
                    type: \App\Models\Notification::TYPE_NEW_EXPENSE,
                    message: "Создан повторяющийся расход: {$recurringExpense->description} - {$recurringExpense->amount}₽",
                    groupId: $recurringExpense->group_id,
                    data: [
                        'recurring_expense_id' => $recurringExpense->id,
                        'description' => $recurringExpense->description,
                        'amount' => $recurringExpense->amount,
                        'schedule' => $recurringExpense->getScheduleDescription(),
                        'next_occurrence' => $recurringExpense->next_occurrence,
                        'creator_name' => $creator->first_name ?? $creator->username
                    ]
                )
            );
        }
    }

    private function notifyRecurringExpenseActivated(RecurringExpense $recurringExpense, User $activator): void
    {
        $participants = $recurringExpense->group->users->where('id', '!=', $activator->id);
        
        foreach ($participants as $participant) {
            $this->notificationService->createNotification(
                new \App\Services\Notifications\DTO\CreateNotificationDTO(
                    userId: $participant->id,
                    type: \App\Models\Notification::TYPE_NEW_EXPENSE,
                    message: "Повторяющийся расход активирован: {$recurringExpense->description}",
                    groupId: $recurringExpense->group_id,
                    data: [
                        'recurring_expense_id' => $recurringExpense->id,
                        'description' => $recurringExpense->description,
                        'next_occurrence' => $recurringExpense->next_occurrence,
                        'activator_name' => $activator->first_name ?? $activator->username
                    ]
                )
            );
        }
    }

    private function notifyRecurringExpenseDeactivated(RecurringExpense $recurringExpense, User $deactivator): void
    {
        $participants = $recurringExpense->group->users->where('id', '!=', $deactivator->id);
        
        foreach ($participants as $participant) {
            $this->notificationService->createNotification(
                new \App\Services\Notifications\DTO\CreateNotificationDTO(
                    userId: $participant->id,
                    type: \App\Models\Notification::TYPE_NEW_EXPENSE,
                    message: "Повторяющийся расход деактивирован: {$recurringExpense->description}",
                    groupId: $recurringExpense->group_id,
                    data: [
                        'recurring_expense_id' => $recurringExpense->id,
                        'description' => $recurringExpense->description,
                        'deactivator_name' => $deactivator->first_name ?? $deactivator->username
                    ]
                )
            );
        }
    }

    private function notifyRecurringExpenseDeleted(RecurringExpense $recurringExpense, User $deleter): void
    {
        $participants = $recurringExpense->group->users->where('id', '!=', $deleter->id);
        
        foreach ($participants as $participant) {
            $this->notificationService->createNotification(
                new \App\Services\Notifications\DTO\CreateNotificationDTO(
                    userId: $participant->id,
                    type: \App\Models\Notification::TYPE_NEW_EXPENSE,
                    message: "Повторяющийся расход удален: {$recurringExpense->description}",
                    groupId: $recurringExpense->group_id,
                    data: [
                        'recurring_expense_id' => $recurringExpense->id,
                        'description' => $recurringExpense->description,
                        'deleter_name' => $deleter->first_name ?? $deleter->username
                    ]
                )
            );
        }
    }

    private function notifyRecurringExpenseSkipped(RecurringExpense $recurringExpense, User $skipper): void
    {
        $participants = $recurringExpense->group->users->where('id', '!=', $skipper->id);
        
        foreach ($participants as $participant) {
            $this->notificationService->createNotification(
                new \App\Services\Notifications\DTO\CreateNotificationDTO(
                    userId: $participant->id,
                    type: \App\Models\Notification::TYPE_NEW_EXPENSE,
                    message: "Пропущено очередное выполнение: {$recurringExpense->description}",
                    groupId: $recurringExpense->group_id,
                    data: [
                        'recurring_expense_id' => $recurringExpense->id,
                        'description' => $recurringExpense->description,
                        'next_occurrence' => $recurringExpense->next_occurrence,
                        'skipper_name' => $skipper->first_name ?? $skipper->username
                    ]
                )
            );
        }
    }

    private function notifyRecurringExpenseGenerated(RecurringExpense $recurringExpense): void
    {
        $participants = $recurringExpense->group->users->where('id', '!=', $recurringExpense->payer_id);
        
        foreach ($participants as $participant) {
            $this->notificationService->createNotification(
                new \App\Services\Notifications\DTO\CreateNotificationDTO(
                    userId: $participant->id,
                    type: \App\Models\Notification::TYPE_NEW_EXPENSE,
                    message: "Автоматически создан расход: {$recurringExpense->description} - {$recurringExpense->amount}₽",
                    groupId: $recurringExpense->group_id,
                    data: [
                        'recurring_expense_id' => $recurringExpense->id,
                        'description' => $recurringExpense->description,
                        'amount' => $recurringExpense->amount,
                        'date' => $recurringExpense->next_occurrence,
                        'payer_name' => $recurringExpense->payer->first_name ?? $recurringExpense->payer->username
                    ]
                )
            );
        }
    }
}