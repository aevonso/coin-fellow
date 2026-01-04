<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'currency',
        'description',
        'invite_code',
    ];

    public function users(): BelongsToMany {
        return $this->members();
    }

    public function members(): BelongsToMany 
    {
        return $this->belongsToMany(User::class, 'group_user')
            ->using(GroupUser::class)
            ->withPivot('role', 'created_at', 'updated_at')
            ->withTimestamps();
    }


    public function expenses(): HasMany {
        return $this->hasMany(Expense::class);
    }

    public function groupUsers(): HasMany {
        return $this->hasMany(GroupUser::class);
    }

    public function getOwner(): ?GroupUser 
    {
        return $this->groupUsers()->where('role', 'owner')->first();
    }

    public function getAdmins() 
    {
        return $this->groupUsers()->whereIn('role', ['owner', 'admin'])->get();
    }

    public function getMembers(){
        return $this->groupUsers()->where('role', 'member')->get();
    } 

    public function scopeWhereUserIsMember($query, User $user)
    {
        return $query->whereHas('users', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        });
    }

    public function scopeWithUserRole($query, User $user)
    {
        return $query->with(['groupUsers' => function ($query) use ($user) {
            $query->where('user_id', $user->id);
        }]);
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
        return $this->groupUsers()
            ->where('user_id', $user->id)
            ->whereIn('role', ['owner', 'admin'])
            ->exists();
    }

    public function getUserRole(User $user): ?string
    {
        return $this->users()
            ->where('user_id', $user->id)
            ->first()
            ?->role;
    }

    public function getMembersCount(): int
    {
        return $this->groupUsers()->count();
    }

    public function getTotalExpenses(): float
    {
        return $this->expenses()->sum('amount');
    }
}
