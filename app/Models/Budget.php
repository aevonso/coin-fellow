<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Budget extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'category_id',
        'user_id',
        'amount',
        'period',
        'start_date',
        'end_date',
        'is_active',
        'notify_on_percentage',
        'currency'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'notify_on_percentage' => 'integer'
    ];

    const PERIOD_DAILY = 'daily';
    const PERIOD_WEEKLY = 'weekly';
    const PERIOD_MONTHLY = 'monthly';
    const PERIOD_YEARLY = 'yearly';

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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
        return $query->where('user_id', $userId);
    }

    public function scopeForCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeCurrent($query)
    {
        return $query->where(function($q) {
            $q->where('start_date', '<=', now())
              ->where(function($q2) {
                  $q2->where('end_date', '>=', now())
                     ->orWhereNull('end_date');
              });
        });
    }

    public function isCurrent(): bool
    {
        return $this->start_date <= now() && 
               ($this->end_date === null || $this->end_date >= now());
    }

    public function getSpentAmount(): float
    {
        $query = Expense::where('group_id', $this->group_id)
            ->where('date', '>=', $this->start_date);

        if ($this->end_date) {
            $query->where('date', '<=', $this->end_date);
        }

        if ($this->category_id) {
            $query->where('category_id', $this->category_id);
        }

        if ($this->user_id) {
            $query->where('payer_id', $this->user_id);
        }

        return (float) $query->sum('amount');
    }

    public function getRemainingAmount(): float
    {
        return max(0, $this->amount - $this->getSpentAmount());
    }

    public function getUsagePercentage(): float
    {
        if ($this->amount == 0) return 0;
        return min(100, ($this->getSpentAmount() / $this->amount) * 100);
    }

    public function isExceeded(): bool
    {
        return $this->getSpentAmount() > $this->amount;
    }

    public function shouldNotify(): bool
    {
        return $this->notify_on_percentage > 0 && 
               $this->getUsagePercentage() >= $this->notify_on_percentage;
    }
}