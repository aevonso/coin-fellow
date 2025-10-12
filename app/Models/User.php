<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $keyType = 'string';
    public $incrementing = false;
    protected $primaryKey = 'id';

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Str::uuid()->toString();
            } 
        }); 
    }

    protected $fillable = [
        'id', 
        'telegram_user_id',
        'username',
        'first_name',
        'last_name',
        'phone',
        'email',
        'password',
        'language_code',
        'refresh_token',
        'refresh_token_expires_at',
        'avatar_url',
        'avatar_telegram_file_id',
        'email_verified_at',
        'phone_verified_at',
        'telegram_verified_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'refresh_token',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'telegram_verified_at' => 'datetime',
        'refresh_token_expires_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function hasActiveSubscription(): bool
    {
        if ($this->relationLoaded('subscription') && $this->subscription) {
            return $this->subscription->status === 'active' && 
                   $this->subscription->ends_at->isFuture();
        }
        
        return $this->premium_until && $this->premium_until->isFuture();
    }

    public function getJWTCustomClaims()
    {
        return [
            'telegram_user_id' => $this->telegram_user_id,
            'username' => $this->username,
            'email' => $this->email,
        ];
    }

    public function scopeByTelegramId($query, $telegramId)
    {
        return $query->where('telegram_user_id', $telegramId);
    }

    public function groups(): BelongsToMany {
        return $this->belongsToMany(Group::class, 'group_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function expenses(): HasMany {
        return $this->hasMany(Expense::class, 'payer_id');
    }

    public function participatingExpenses(): BelongsToMany {
        return $this->belongsToMany(Expense::class, 'expense_user')
            ->withPivot(['share', 'percentage'])
            ->withTimestamp();
    }
}