<?php

namespace Tests\Unit\DataTransferObjects;

use App\DataTransferObjects\TeamPrediction;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamPredictionTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_array_serializes_expected_fields(): void
    {
        $team = Team::factory()->create(['name' => 'Alpha', 'short_name' => 'ALP']);
        $prediction = new TeamPrediction(team: $team, probability: 62.3);

        $payload = $prediction->toArray();

        $this->assertSame('Alpha', $payload['team']['name']);
        $this->assertSame('ALP', $payload['team']['short_name']);
        $this->assertSame(62.3, $payload['probability']);
    }
}
