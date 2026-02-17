<?php

namespace Tests\Unit\Services;

use App\DataTransferObjects\TeamStanding;
use App\Models\Fixture;
use App\Models\GameMatch;
use App\Models\Team;
use App\Services\LeagueTableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeagueTableServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_league_returns_zeroed_standings_and_positions(): void
    {
        Team::factory()->count(4)->create();
        $table = (new LeagueTableService())->getTable();

        $this->assertCount(4, $table);
        $this->assertSame([1, 2, 3, 4], array_map(fn (TeamStanding $s): int => $s->position, $table));
        foreach ($table as $standing) {
            $this->assertSame(0, $standing->points);
            $this->assertSame(0, $standing->goalDifference);
        }
    }

    public function test_points_and_goal_difference_rules(): void
    {
        [$a, $b, $c, $d] = Team::factory()->count(4)->create();
        $fixture = Fixture::factory()->create(['is_played' => true]);
        GameMatch::factory()->create(['fixture_id' => $fixture->id, 'home_team_id' => $a->id, 'away_team_id' => $b->id, 'home_score' => 2, 'away_score' => 1, 'is_played' => true]);
        GameMatch::factory()->create(['fixture_id' => $fixture->id, 'home_team_id' => $c->id, 'away_team_id' => $d->id, 'home_score' => 0, 'away_score' => 0, 'is_played' => true]);

        $table = (new LeagueTableService())->getTable();
        $aStanding = $this->byId($table, $a->id);
        $bStanding = $this->byId($table, $b->id);
        $cStanding = $this->byId($table, $c->id);

        $this->assertSame(3, $aStanding->points);
        $this->assertSame(0, $bStanding->points);
        $this->assertSame(1, $cStanding->points);
        $this->assertSame(1, $aStanding->goalDifference);
    }

    public function test_tiebreakers_and_full_season_and_in_memory_path(): void
    {
        [$a, $b, $c, $d] = Team::factory()->count(4)->create();
        $f1 = Fixture::factory()->create(['week' => 1, 'is_played' => true]);
        $f2 = Fixture::factory()->create(['week' => 2, 'is_played' => true]);
        $f3 = Fixture::factory()->create(['week' => 3, 'is_played' => true]);

        GameMatch::factory()->create(['fixture_id' => $f1->id, 'home_team_id' => $a->id, 'away_team_id' => $c->id, 'home_score' => 2, 'away_score' => 1, 'is_played' => true]);
        GameMatch::factory()->create(['fixture_id' => $f1->id, 'home_team_id' => $b->id, 'away_team_id' => $d->id, 'home_score' => 1, 'away_score' => 0, 'is_played' => true]);
        GameMatch::factory()->create(['fixture_id' => $f2->id, 'home_team_id' => $a->id, 'away_team_id' => $b->id, 'home_score' => 1, 'away_score' => 1, 'is_played' => true]);
        GameMatch::factory()->create(['fixture_id' => $f2->id, 'home_team_id' => $c->id, 'away_team_id' => $d->id, 'home_score' => 0, 'away_score' => 0, 'is_played' => true]);
        GameMatch::factory()->create(['fixture_id' => $f3->id, 'home_team_id' => $a->id, 'away_team_id' => $d->id, 'home_score' => 0, 'away_score' => 2, 'is_played' => true]);
        GameMatch::factory()->create(['fixture_id' => $f3->id, 'home_team_id' => $b->id, 'away_team_id' => $c->id, 'home_score' => 2, 'away_score' => 2, 'is_played' => true]);

        $service = new LeagueTableService();
        $dbTable = $service->getTable();

        $results = GameMatch::query()->where('is_played', true)->get(['home_team_id','away_team_id','home_score','away_score'])->toArray();
        $arrTable = (new LeagueTableService())->setTeams(Team::query()->orderBy('name')->get())->getTableFromResults($results);

        $this->assertSame(
            array_map(fn (TeamStanding $s): int => $s->team->id, $dbTable),
            array_map(fn (TeamStanding $s): int => $s->team->id, $arrTable)
        );

        // Sorting by points first
        $this->assertGreaterThanOrEqual($dbTable[1]->points, $dbTable[0]->points);

        // Calculate standing from array path
        $one = (new LeagueTableService())->calculateStandingFromArray($a, $results);
        $this->assertSame($one->goalDifference, $one->goalsFor - $one->goalsAgainst);
    }

    public function test_three_team_tie_mini_league_h2h_paths(): void
    {
        [$a, $b, $c, $d] = Team::factory()->count(4)->create();
        $fixture = Fixture::factory()->create(['is_played' => true]);
        // A,B,C tie on points from mutual games; include unplayed-excluded by feeding results manually
        $results = [
            ['home_team_id' => $a->id, 'away_team_id' => $b->id, 'home_score' => 1, 'away_score' => 0],
            ['home_team_id' => $b->id, 'away_team_id' => $c->id, 'home_score' => 1, 'away_score' => 0],
            ['home_team_id' => $c->id, 'away_team_id' => $a->id, 'home_score' => 1, 'away_score' => 0],
            ['home_team_id' => $d->id, 'away_team_id' => $a->id, 'home_score' => 0, 'away_score' => 2],
            ['home_team_id' => $d->id, 'away_team_id' => $b->id, 'home_score' => 0, 'away_score' => 2],
            ['home_team_id' => $d->id, 'away_team_id' => $c->id, 'home_score' => 0, 'away_score' => 1],
        ];

        $table = (new LeagueTableService())->setTeams(collect([$a, $b, $c, $d]))->getTableFromResults($results);
        $this->assertCount(4, $table);
        $this->assertSame([1,2,3,4], array_map(fn (TeamStanding $s): int => $s->position, $table));

        // H2H GD tie-break variant
        $results[2] = ['home_team_id' => $c->id, 'away_team_id' => $a->id, 'home_score' => 2, 'away_score' => 0];
        $table2 = (new LeagueTableService())->setTeams(collect([$a, $b, $c, $d]))->getTableFromResults($results);
        $this->assertCount(4, $table2);

        // mini-league with missing matches (partial set) still deterministic
        $partial = array_slice($results, 0, 4);
        $table3 = (new LeagueTableService())->setTeams(collect([$a, $b, $c, $d]))->getTableFromResults($partial);
        $this->assertCount(4, $table3);
    }

    /** @param array<TeamStanding> $table */
    private function byId(array $table, int $id): TeamStanding
    {
        foreach ($table as $standing) {
            if ($standing->team->id === $id) {
                return $standing;
            }
        }

        $this->fail('standing not found');
    }
}
