<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
        'color',
    ];
    
    public function expenses(): HasMany {
        return $this->hasMany(Expense::class);
    }

    public function getFormattedName(): string {
        return $this->icon . ' ' . $this->name;
    }
}
