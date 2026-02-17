<?php

namespace Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    protected $model = Team::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'short_name' => strtoupper(fake()->unique()->lexify('???')),
            'power' => fake()->numberBetween(50, 95),
            'home_advantage' => fake()->numberBetween(5, 18),
            'goalkeeper_factor' => fake()->numberBetween(5, 18),
            'supporter_strength' => fake()->numberBetween(5, 18),
        ];
    }
}
