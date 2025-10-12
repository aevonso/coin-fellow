<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use Hasfactory;

    protected $fillable = [
        'name',
        'currency',
        'description',
        'invite_code',
    ];

    public function users(): BelongsToMany {
        return $this->belongsToMany(User::class, 'group_user')
            ->withPivot('role')
            ->withTimestamps();
    }


    public function expenses(): HasMany {
        return $this->hasMany(Expense::class);
    }

     public function scopeWhereUserIsMember($query, User $user)
    {
        return $query->whereHas('users', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }

    public function scopeWithUserRole($query, User $user)
    {
        return $query->with(['users' => function ($query) use ($user) {
            $query->where('user_id', $user->id)->select('role');
        }]);
    }

    public function getOwner(): ?User
    {
        return $this->users()->wherePivot('role', 'owner')->first();
    }

    public function isUserOwner(User $user): bool
    {
        return $this->users()
            ->where('user_id', $user->id)
            ->wherePivot('role', 'owner')
            ->exists();
    }

    public function isUserAdmin(User $user): bool
    {
        return $this->users()
            ->where('user_id', $user->id)
            ->whereIn('pivot_role', ['owner', 'admin'])
            ->exists();
    }

    public function getUserRole(User $user): ?string
    {
        return $this->users()
            ->where('user_id', $user->id)
            ->first()
            ?->pivot
            ->role ?? null;
    }

    public function getMembersCount(): int
    {
        return $this->users()->count();
    }

    public function getTotalExpenses(): float
    {
        return $this->expenses()->sum('amount');
    }
}
