<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fixture extends Model
{
    use HasFactory;

    protected $fillable = [
        'week',
        'is_played',
    ];

    protected $casts = [
        'is_played' => 'boolean',
    ];

    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'fixture_id');
    }

    public function scopePlayed(Builder $query): Builder
    {
        return $query->where('is_played', true);
    }

    public function scopeUnplayed(Builder $query): Builder
    {
        return $query->where('is_played', false);
    }
}
