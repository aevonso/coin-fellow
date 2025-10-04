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

    //В дальнейшем реализую, когда создам другие модели :)

    // public function expenses(): HasMany {
    //     return $this->hasMany()
    // }
}
