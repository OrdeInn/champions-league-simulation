<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    public function run(): void
    {
        $teams = [
            [
                'name' => 'Real Madrid',
                'short_name' => 'RMA',
                'power' => 90,
                'home_advantage' => 16,
                'goalkeeper_factor' => 17,
                'supporter_strength' => 18,
            ],
            [
                'name' => 'Liverpool',
                'short_name' => 'LIV',
                'power' => 85,
                'home_advantage' => 15,
                'goalkeeper_factor' => 16,
                'supporter_strength' => 17,
            ],
            [
                'name' => 'Bayern Munich',
                'short_name' => 'BAY',
                'power' => 82,
                'home_advantage' => 14,
                'goalkeeper_factor' => 15,
                'supporter_strength' => 15,
            ],
            [
                'name' => 'Galatasaray',
                'short_name' => 'GAL',
                'power' => 72,
                'home_advantage' => 18,
                'goalkeeper_factor' => 12,
                'supporter_strength' => 19,
            ],
        ];

        foreach ($teams as $team) {
            Team::updateOrCreate(['short_name' => $team['short_name']], $team);
        }
    }
}
