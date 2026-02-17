<?php

namespace Tests\Unit\DataTransferObjects;

use App\DataTransferObjects\TeamStanding;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamStandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_array_serializes_expected_fields(): void
    {
        $team = Team::factory()->create(['name' => 'Alpha', 'short_name' => 'ALP']);

        $standing = new TeamStanding(
            team: $team,
            played: 6,
            won: 3,
            drawn: 2,
            lost: 1,
            goalsFor: 10,
            goalsAgainst: 7,
            goalDifference: 3,
            points: 11,
            position: 1,
        );

        $payload = $standing->toArray();

        $this->assertSame('Alpha', $payload['team']['name']);
        $this->assertSame('ALP', $payload['team']['short_name']);
        $this->assertSame(11, $payload['points']);
        $this->assertSame(1, $payload['position']);
    }
}
