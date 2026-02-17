<?php

namespace Database\Factories;

use App\Models\Fixture;
use App\Models\GameMatch;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameMatch>
 */
class GameMatchFactory extends Factory
{
    protected $model = GameMatch::class;

    public function definition(): array
    {
        return [
            'fixture_id' => Fixture::factory(),
            'home_team_id' => Team::factory(),
            'away_team_id' => Team::factory(),
            'home_score' => null,
            'away_score' => null,
            'is_played' => false,
        ];
    }

    public function played(): self
    {
        return $this->state(fn (): array => [
            'home_score' => fake()->numberBetween(0, 4),
            'away_score' => fake()->numberBetween(0, 4),
            'is_played' => true,
        ]);
    }
}
