<?php

namespace Tests\Unit\Models;

use App\Models\GameMatch;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameMatchTest extends TestCase
{
    use RefreshDatabase;

    public function test_relationships_and_result_accessor(): void
    {
        $home = Team::factory()->create();
        $away = Team::factory()->create();

        $match = GameMatch::factory()->create([
            'home_team_id' => $home->id,
            'away_team_id' => $away->id,
            'home_score' => 2,
            'away_score' => 1,
            'is_played' => true,
        ]);

        $this->assertSame($home->id, $match->homeTeam->id);
        $this->assertSame($away->id, $match->awayTeam->id);
        $this->assertSame('2 - 1', $match->result);
        $this->assertSame($home->id, $match->winner?->id);
    }

    public function test_unplayed_match_accessors(): void
    {
        $match = GameMatch::factory()->create();

        $this->assertSame('vs', $match->result);
        $this->assertNull($match->winner);
    }
}
