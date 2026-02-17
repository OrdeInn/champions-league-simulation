<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'short_name',
        'power',
        'home_advantage',
        'goalkeeper_factor',
        'supporter_strength',
    ];

    protected $casts = [
        'power' => 'integer',
        'home_advantage' => 'integer',
        'goalkeeper_factor' => 'integer',
        'supporter_strength' => 'integer',
    ];

    public function homeMatches(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'home_team_id');
    }

    public function awayMatches(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'away_team_id');
    }

    public function getAllMatchesAttribute(): Collection
    {
        return $this->homeMatches->merge($this->awayMatches);
    }

    public function getOverallStrengthAttribute(): int
    {
        return $this->power + $this->goalkeeper_factor + $this->supporter_strength;
    }
}
