<?php

namespace App\DataTransferObjects;

use App\Models\Team;

class TeamStanding
{
    public function __construct(
        public readonly Team $team,
        public readonly int $played,
        public readonly int $won,
        public readonly int $drawn,
        public readonly int $lost,
        public readonly int $goalsFor,
        public readonly int $goalsAgainst,
        public readonly int $goalDifference,
        public readonly int $points,
        public readonly int $position,
    ) {}

    public function withPosition(int $position): self
    {
        return new self(
            team: $this->team,
            played: $this->played,
            won: $this->won,
            drawn: $this->drawn,
            lost: $this->lost,
            goalsFor: $this->goalsFor,
            goalsAgainst: $this->goalsAgainst,
            goalDifference: $this->goalDifference,
            points: $this->points,
            position: $position,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'team' => [
                'id' => $this->team->id,
                'name' => $this->team->name,
                'short_name' => $this->team->short_name,
            ],
            'played' => $this->played,
            'won' => $this->won,
            'drawn' => $this->drawn,
            'lost' => $this->lost,
            'goals_for' => $this->goalsFor,
            'goals_against' => $this->goalsAgainst,
            'goal_difference' => $this->goalDifference,
            'points' => $this->points,
            'position' => $this->position,
        ];
    }
}
