<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class GroupUser extends Pivot
{
    protected $table = 'group_user';

    protected $fillable = [
        'group_id',
        'user_id',
        'role',
    ];

    protected $casts = [
        'user_id' => 'string',
    ];

    public function group() 
    {
        return $this->belongsTo(Group::class);
    }

    public function user() 
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOwner($query) 
    {
        return $query->where('role', 'owner');
    }

    public function scopeAdmin($query) 
    {
        return $query->where('role', 'admin');
    }

    public function scopeMember($query) 
    {
        return $query->where('role', 'member');
    }

    public function isOwner(): bool 
    {
        return $this->role === 'owner';
    }

    public function isAdmin(): bool 
    {
        return $this->role === 'admin';
    }
}
