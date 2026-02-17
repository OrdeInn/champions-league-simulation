<?php

namespace Tests\Unit\Models;

use App\Models\GameMatch;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_and_away_relationships_work(): void
    {
        $team = Team::factory()->create();
        $opponent = Team::factory()->create();

        GameMatch::factory()->create([
            'home_team_id' => $team->id,
            'away_team_id' => $opponent->id,
        ]);

        GameMatch::factory()->create([
            'home_team_id' => $opponent->id,
            'away_team_id' => $team->id,
        ]);

        $this->assertCount(1, $team->homeMatches);
        $this->assertCount(1, $team->awayMatches);
        $this->assertCount(2, $team->all_matches);
    }

    public function test_overall_strength_accessor(): void
    {
        $team = Team::factory()->create([
            'power' => 90,
            'goalkeeper_factor' => 15,
            'supporter_strength' => 17,
        ]);

        $this->assertSame(122, $team->overall_strength);
    }
}
