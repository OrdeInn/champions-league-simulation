<?php

namespace Tests\Unit\Models;

use App\Models\Fixture;
use App\Models\GameMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixtureTest extends TestCase
{
    use RefreshDatabase;

    public function test_played_and_unplayed_scopes(): void
    {
        Fixture::factory()->create(['is_played' => true]);
        Fixture::factory()->create(['is_played' => false]);

        $this->assertSame(1, Fixture::query()->played()->count());
        $this->assertSame(1, Fixture::query()->unplayed()->count());
    }

    public function test_matches_relationship(): void
    {
        $fixture = Fixture::factory()->create();
        GameMatch::factory()->count(2)->create(['fixture_id' => $fixture->id]);

        $this->assertCount(2, $fixture->matches);
    }
}
