<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'payer_id',
        'category_id',
        'description',
        'amount',
        'frequency',
        'start_date',
        'end_date',
        'day_of_month',
        'weekdays',
        'next_occurrence',
        'is_active',
        'last_generated_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'next_occurrence' => 'date',
        'last_generated_at' => 'datetime',
        'day_of_month' => 'integer',
        'weekdays' => 'array',
        'is_active' => 'boolean'
    ];

    const FREQUENCY_DAILY = 'daily';
    const FREQUENCY_WEEKLY = 'weekly';
    const FREQUENCY_MONTHLY = 'monthly';
    const FREQUENCY_YEARLY = 'yearly';
    const FREQUENCY_CUSTOM = 'custom';

    const WEEKDAYS = [
        'monday' => 1,
        'tuesday' => 2,
        'wednesday' => 3,
        'thursday' => 4,
        'friday' => 5,
        'saturday' => 6,
        'sunday' => 7
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'payer_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForGroup($query, $groupId)
    {
        return $query->where('group_id', $groupId);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('payer_id', $userId);
    }

    public function scopeDueForGeneration($query)
    {
        return $query->where('is_active', true)
            ->where('next_occurrence', '<=', now()->toDateString())
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', now()->toDateString());
            });
    }

    public function isDue(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->end_date && $this->end_date < now()) {
            return false;
        }

        return $this->next_occurrence <= now()->toDateString();
    }

    public function calculateNextOccurrence(): ?string
    {
        if (!$this->is_active) {
            return null;
        }

        $lastDate = $this->next_occurrence ?? $this->start_date;

        return match($this->frequency) {
            self::FREQUENCY_DAILY => $this->calculateDailyNext($lastDate),
            self::FREQUENCY_WEEKLY => $this->calculateWeeklyNext($lastDate),
            self::FREQUENCY_MONTHLY => $this->calculateMonthlyNext($lastDate),
            self::FREQUENCY_YEARLY => $this->calculateYearlyNext($lastDate),
            self::FREQUENCY_CUSTOM => $this->calculateCustomNext($lastDate),
            default => null
        };
    }

    private function calculateDailyNext(string $lastDate): string
    {
        return date('Y-m-d', strtotime($lastDate . ' +1 day'));
    }

    private function calculateWeeklyNext(string $lastDate): string
    {
        if ($this->weekdays && is_array($this->weekdays)) {
            $currentWeekday = date('N', strtotime($lastDate));
            $nextWeekday = $this->findNextWeekday($currentWeekday, $this->weekdays);
            $daysToAdd = ($nextWeekday - $currentWeekday + 7) % 7;
            $daysToAdd = $daysToAdd === 0 ? 7 : $daysToAdd;
            return date('Y-m-d', strtotime($lastDate . " +{$daysToAdd} days"));
        }
        
        return date('Y-m-d', strtotime($lastDate . ' +1 week'));
    }

    private function calculateMonthlyNext(string $lastDate): string
    {
        if ($this->day_of_month) {
            $nextDate = date('Y-m', strtotime($lastDate . ' +1 month')) . '-' . str_pad($this->day_of_month, 2, '0', STR_PAD_LEFT);
            
            if (!checkdate(date('m', strtotime($nextDate)), date('d', strtotime($nextDate)), date('Y', strtotime($nextDate)))) {
                $nextDate = date('Y-m-t', strtotime($nextDate));
            }
            
            return $nextDate;
        }
        
        return date('Y-m-d', strtotime($lastDate . ' +1 month'));
    }

    private function calculateYearlyNext(string $lastDate): string
    {
        return date('Y-m-d', strtotime($lastDate . ' +1 year'));
    }

    private function calculateCustomNext(string $lastDate): ?string
    {
        return null;
    }

    private function findNextWeekday(int $currentWeekday, array $weekdays): int
    {
        sort($weekdays);
        
        foreach ($weekdays as $weekday) {
            if ($weekday > $currentWeekday) {
                return $weekday;
            }
        }
        
        return $weekdays[0];
    }

    public function getScheduleDescription(): string
    {
        return match($this->frequency) {
            self::FREQUENCY_DAILY => 'Ежедневно',
            self::FREQUENCY_WEEKLY => $this->getWeeklyDescription(),
            self::FREQUENCY_MONTHLY => $this->getMonthlyDescription(),
            self::FREQUENCY_YEARLY => 'Ежегодно',
            self::FREQUENCY_CUSTOM => 'По расписанию',
            default => 'Неизвестно'
        };
    }

    private function getWeeklyDescription(): string
    {
        if ($this->weekdays && is_array($this->weekdays)) {
            $days = array_map(function ($dayNum) {
                return $this->getWeekdayName($dayNum);
            }, $this->weekdays);
            
            return 'Еженедельно по: ' . implode(', ', $days);
        }
        
        return 'Еженедельно';
    }

    private function getMonthlyDescription(): string
    {
        if ($this->day_of_month) {
            return 'Ежемесячно, ' . $this->day_of_month . '-го числа';
        }
        
        return 'Ежемесячно';
    }

    private function getWeekdayName(int $dayNum): string
    {
        $names = [
            1 => 'Понедельник',
            2 => 'Вторник',
            3 => 'Среда',
            4 => 'Четверг',
            5 => 'Пятница',
            6 => 'Суббота',
            7 => 'Воскресенье'
        ];
        
        return $names[$dayNum] ?? 'День ' . $dayNum;
    }
}