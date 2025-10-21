<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use  Illuminate\Database\Eloquent\Factories\BelongsTo;

class Balance extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'from_user_id',
        'to_user_id',
        'amount'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function group(): BelongsTo {
        return $this->belongsTo(Group::class);
    }

    public function fromUser(): BelongsTo {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function scopeBetweenUsers($query, $fromUserId, $toUserId) {
        return $query->where('from_user_id', $fromUserId)
                    ->where('to_user_id', $toUserId);
    }

    public function scopeForGroup($query, $groupId) {
        return $query->where('group_id', $groupId);
    }

    public function scopeForUser($query, $userId) {
        return $query->where('from_user_id', $userId)
                    ->where('to_user_id', $userId);
    }
}
