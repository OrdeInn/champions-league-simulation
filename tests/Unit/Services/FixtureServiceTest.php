<?php

namespace Tests\Unit\Services;

use App\Models\Fixture;
use App\Models\GameMatch;
use App\Models\Team;
use App\Services\FixtureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class FixtureServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_six_weeks_and_twelve_matches(): void
    {
        Team::factory()->count(4)->create();

        (new FixtureService())->generate();

        $this->assertSame(6, Fixture::query()->count());
        $this->assertSame(12, GameMatch::query()->count());
    }

    public function test_each_team_plays_once_per_week_and_no_self_matches(): void
    {
        Team::factory()->count(4)->create();
        (new FixtureService())->generate();

        foreach (Fixture::with('matches')->orderBy('week')->get() as $fixture) {
            $teamIds = [];
            foreach ($fixture->matches as $match) {
                $this->assertNotSame($match->home_team_id, $match->away_team_id);
                $teamIds[] = $match->home_team_id;
                $teamIds[] = $match->away_team_id;
            }
            sort($teamIds);
            $this->assertCount(4, array_unique($teamIds));
        }
    }

    public function test_each_pair_meets_home_and_away(): void
    {
        Team::factory()->count(4)->create();
        (new FixtureService())->generate();

        $pairs = [];
        foreach (GameMatch::all() as $match) {
            $key = min($match->home_team_id, $match->away_team_id).'-'.max($match->home_team_id, $match->away_team_id);
            $pairs[$key][] = [$match->home_team_id, $match->away_team_id];
        }

        $this->assertCount(6, $pairs);
        foreach ($pairs as $meetings) {
            $this->assertCount(2, $meetings);
            $this->assertSame($meetings[0][0], $meetings[1][1]);
            $this->assertSame($meetings[0][1], $meetings[1][0]);
        }
    }

    public function test_regeneration_replaces_old_fixtures_and_matches_start_unplayed(): void
    {
        Team::factory()->count(4)->create();
        $service = new FixtureService();

        $service->generate();
        $firstFixtureIds = Fixture::query()->pluck('id')->all();

        $service->generate();

        $this->assertSame(6, Fixture::query()->count());
        $this->assertSame(12, GameMatch::query()->count());
        $this->assertNotSame($firstFixtureIds, Fixture::query()->pluck('id')->all());
        $this->assertSame(12, GameMatch::query()->where('is_played', false)->whereNull('home_score')->whereNull('away_score')->count());
    }

    public function test_throws_if_not_four_teams(): void
    {
        Team::factory()->count(3)->create();

        $this->expectException(InvalidArgumentException::class);
        (new FixtureService())->generate();
    }
}
