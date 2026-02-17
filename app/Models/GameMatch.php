<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameMatch extends Model
{
    use HasFactory;

    protected $table = 'matches';

    protected $fillable = [
        'fixture_id',
        'home_team_id',
        'away_team_id',
        'home_score',
        'away_score',
        'is_played',
    ];

    protected $casts = [
        'home_score' => 'integer',
        'away_score' => 'integer',
        'is_played' => 'boolean',
    ];

    public function fixture(): BelongsTo
    {
        return $this->belongsTo(Fixture::class, 'fixture_id');
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function getResultAttribute(): string
    {
        if (! $this->is_played || $this->home_score === null || $this->away_score === null) {
            return 'vs';
        }

        return $this->home_score . ' - ' . $this->away_score;
    }

    public function getWinnerAttribute(): ?Team
    {
        if (! $this->is_played || $this->home_score === null || $this->away_score === null) {
            return null;
        }

        if ($this->home_score > $this->away_score) {
            return $this->homeTeam;
        }

        if ($this->away_score > $this->home_score) {
            return $this->awayTeam;
        }

        return null;
    }
}
