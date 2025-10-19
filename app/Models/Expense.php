<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Expense extends Model //модель для расчетов
{
    use HasFactory;

    protected $fillable = [
        'group_id',
        'payer_id',
        'category_id',
        'description',
        'amount',
        'date'
    ];

    protected $casts = [
        'amount' => 'decimal:2', //исправил на decimal:2
        'date' => 'date',
    ];


    //fk

    public function payer(): BelongsTo {
        return $this->belongsTo(User::class, 'payer_id');
    }

    public function group(): BelongsTo {
        return $this->belongsTo(Group::class);
    }

    public function category(): BelongsTo {
        return $this->belongsTo(Category::class);
    }
    
    public function participants(): BelongsToMany {
        return $this->belongsToMany(User::class, 'expense_user')
            ->withPivot(['share', 'percentage'])
            ->withTimestamps();
    }


    //scopes

    public function scopeForGroup($query, $groupId) {
        return $query->where('group_id', $groupId);
    }

   public function scopeForUser($query, $userId) {
    return $query->where('payer_id', $userId) 
        ->orWhereHas('participants', function($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    public function getAmountPerParticipant(): float {
        $participantsCount = $this->participants()->count();
        return $participantsCount > 0 ? $this->amount / $participantsCount : 0;
    }

    public function isParticipant(User $user) : bool {
        return $this->participants()->where('user_id', $user->id)->exists();
    }
}
