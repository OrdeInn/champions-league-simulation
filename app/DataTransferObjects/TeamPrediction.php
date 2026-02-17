<?php

namespace App\DataTransferObjects;

use App\Models\Team;

class TeamPrediction
{
    public function __construct(
        public readonly Team $team,
        public readonly float $probability,
    ) {}

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
            'probability' => $this->probability,
        ];
    }
}
