<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
