<?php

namespace Tests\Feature\Services;

use App\DataTransferObjects\TeamStanding;
use App\Models\Fixture;
use App\Models\GameMatch;
use App\Models\Team;
use App\Services\FixtureService;
use App\Services\LeagueTableService;
use App\Services\MatchSimulationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class LeagueTableServiceTest extends TestCase
{
    use RefreshDatabase;

    private int $weekNumber = 1;

    public function test_empty_league_returns_zeroed_table_sorted_alphabetically(): void
    {
        $this->createTeam('Real Madrid', 'RMA', 90);
        $this->createTeam('Bayern Munich', 'BAY', 82);
        $this->createTeam('Galatasaray', 'GAL', 72);
        $this->createTeam('Liverpool', 'LIV', 85);

        $table = (new LeagueTableService())->getTable();

        $this->assertCount(4, $table);
        $this->assertSame(
            ['Bayern Munich', 'Galatasaray', 'Liverpool', 'Real Madrid'],
            array_map(fn (TeamStanding $standing): string => $standing->team->name, $table)
        );

        foreach ($table as $index => $standing) {
            $this->assertSame($index + 1, $standing->position);
            $this->assertSame(0, $standing->played);
            $this->assertSame(0, $standing->won);
            $this->assertSame(0, $standing->drawn);
            $this->assertSame(0, $standing->lost);
            $this->assertSame(0, $standing->goalsFor);
            $this->assertSame(0, $standing->goalsAgainst);
            $this->assertSame(0, $standing->goalDifference);
            $this->assertSame(0, $standing->points);
        }
    }

    public function test_after_one_week_standings_reflect_points_and_stats(): void
    {
        $alpha = $this->createTeam('Alpha FC', 'ALP', 85);
        $beta = $this->createTeam('Beta FC', 'BET', 82);
        $charlie = $this->createTeam('Charlie FC', 'CHA', 80);
        $delta = $this->createTeam('Delta FC', 'DEL', 78);

        $this->createPlayedMatch($alpha, $beta, 2, 1);
        $this->createPlayedMatch($charlie, $delta, 0, 0);

        $table = (new LeagueTableService())->getTable();

        $alphaStanding = $this->standingByShortName($table, 'ALP');
        $betaStanding = $this->standingByShortName($table, 'BET');
        $charlieStanding = $this->standingByShortName($table, 'CHA');
        $deltaStanding = $this->standingByShortName($table, 'DEL');

        $this->assertSame(3, $alphaStanding->points);
        $this->assertSame(1, $alphaStanding->won);
        $this->assertSame(1, $alphaStanding->played);
        $this->assertSame(1, $alphaStanding->goalDifference);

        $this->assertSame(0, $betaStanding->points);
        $this->assertSame(1, $betaStanding->lost);
        $this->assertSame(-1, $betaStanding->goalDifference);

        $this->assertSame(1, $charlieStanding->points);
        $this->assertSame(1, $charlieStanding->drawn);
        $this->assertSame(0, $charlieStanding->goalDifference);

        $this->assertSame(1, $deltaStanding->points);
        $this->assertSame(1, $deltaStanding->drawn);
        $this->assertSame(0, $deltaStanding->goalDifference);
    }

    public function test_points_system_is_three_one_zero_for_win_draw_loss(): void
    {
        $alpha = $this->createTeam('Alpha FC', 'ALP', 85);
        $beta = $this->createTeam('Beta FC', 'BET', 82);
        $charlie = $this->createTeam('Charlie FC', 'CHA', 80);

        $this->createPlayedMatch($alpha, $beta, 1, 0);
        $this->createPlayedMatch($alpha, $charlie, 2, 2);

        $table = (new LeagueTableService())->getTable();

        $alphaStanding = $this->standingByShortName($table, 'ALP');
        $betaStanding = $this->standingByShortName($table, 'BET');
        $charlieStanding = $this->standingByShortName($table, 'CHA');

        $this->assertSame(4, $alphaStanding->points);
        $this->assertSame(0, $betaStanding->points);
        $this->assertSame(1, $charlieStanding->points);
    }

    public function test_goal_difference_is_calculated_across_multiple_matches(): void
    {
        $alpha = $this->createTeam('Alpha FC', 'ALP', 85);
        $beta = $this->createTeam('Beta FC', 'BET', 82);
        $charlie = $this->createTeam('Charlie FC', 'CHA', 80);

        $this->createPlayedMatch($alpha, $beta, 3, 1);
        $this->createPlayedMatch($charlie, $alpha, 1, 1);

        $table = (new LeagueTableService())->getTable();
        $alphaStanding = $this->standingByShortName($table, 'ALP');

        $this->assertSame(4, $alphaStanding->goalsFor);
        $this->assertSame(2, $alphaStanding->goalsAgainst);
        $this->assertSame(2, $alphaStanding->goalDifference);
    }

    public function test_tiebreaker_by_goal_difference_when_points_are_equal(): void
    {
        $alpha = $this->createTeam('Alpha FC', 'ALP', 85);
        $beta = $this->createTeam('Beta FC', 'BET', 82);
        $charlie = $this->createTeam('Charlie FC', 'CHA', 80);
        $delta = $this->createTeam('Delta FC', 'DEL', 78);

        $this->createPlayedMatch($alpha, $charlie, 3, 0);
        $this->createPlayedMatch($beta, $delta, 1, 0);

        $table = (new LeagueTableService())->getTable();

        $this->assertSame('ALP', $table[0]->team->short_name);
        $this->assertSame('BET', $table[1]->team->short_name);
        $this->assertSame(3, $table[0]->points);
        $this->assertSame(3, $table[1]->points);
    }

    public function test_tiebreaker_by_goals_scored_when_points_and_goal_difference_are_equal(): void
    {
        $alpha = $this->createTeam('Alpha FC', 'ALP', 85);
        $beta = $this->createTeam('Beta FC', 'BET', 82);
        $charlie = $this->createTeam('Charlie FC', 'CHA', 80);
        $delta = $this->createTeam('Delta FC', 'DEL', 78);

        $this->createPlayedMatch($alpha, $charlie, 2, 1);
        $this->createPlayedMatch($beta, $delta, 1, 0);

        $table = (new LeagueTableService())->getTable();

        $this->assertSame('ALP', $table[0]->team->short_name);
        $this->assertSame('BET', $table[1]->team->short_name);
        $this->assertSame(1, $table[0]->goalDifference);
        $this->assertSame(1, $table[1]->goalDifference);
        $this->assertSame(2, $table[0]->goalsFor);
        $this->assertSame(1, $table[1]->goalsFor);
    }

    public function test_head_to_head_tiebreaker_works_when_overall_stats_are_equal(): void
    {
        $alpha = $this->createTeam('Alpha FC', 'ALP', 85);
        $beta = $this->createTeam('Beta FC', 'BET', 82);
        $charlie = $this->createTeam('Charlie FC', 'CHA', 80);
        $delta = $this->createTeam('Delta FC', 'DEL', 78);

        $this->createPlayedMatch($alpha, $beta, 1, 0);
        $this->createPlayedMatch($beta, $alpha, 2, 2);
        $this->createPlayedMatch($alpha, $charlie, 0, 0);
        $this->createPlayedMatch($alpha, $delta, 0, 1);
        $this->createPlayedMatch($beta, $charlie, 1, 0);
        $this->createPlayedMatch($beta, $delta, 0, 0);

        $table = (new LeagueTableService())->getTable();
        $alphaStanding = $this->standingByShortName($table, 'ALP');
        $betaStanding = $this->standingByShortName($table, 'BET');

        $this->assertSame(5, $alphaStanding->points);
        $this->assertSame(5, $betaStanding->points);
        $this->assertSame(0, $alphaStanding->goalDifference);
        $this->assertSame(0, $betaStanding->goalDifference);
        $this->assertSame(3, $alphaStanding->goalsFor);
        $this->assertSame(3, $betaStanding->goalsFor);
        $this->assertLessThan($betaStanding->position, $alphaStanding->position);
    }

    public function test_get_table_from_results_matches_db_table_for_same_results(): void
    {
        $alpha = $this->createTeam('Alpha FC', 'ALP', 85);
        $beta = $this->createTeam('Beta FC', 'BET', 82);
        $charlie = $this->createTeam('Charlie FC', 'CHA', 80);
        $delta = $this->createTeam('Delta FC', 'DEL', 78);

        $this->createPlayedMatch($alpha, $beta, 2, 1);
        $this->createPlayedMatch($charlie, $delta, 1, 1);
        $this->createPlayedMatch($alpha, $charlie, 0, 1);

        $service = new LeagueTableService();
        $tableFromDb = $service->getTable();

        $results = GameMatch::query()
            ->where('is_played', true)
            ->get(['home_team_id', 'away_team_id', 'home_score', 'away_score'])
            ->map(fn (GameMatch $match): array => [
                'home_team_id' => $match->home_team_id,
                'away_team_id' => $match->away_team_id,
                'home_score' => $match->home_score,
                'away_score' => $match->away_score,
            ])
            ->all();

        $tableFromArray = (new LeagueTableService())
            ->setTeams(Team::query()->orderBy('name')->get())
            ->getTableFromResults($results);

        $this->assertSame(
            $this->normalizeStandings($tableFromDb),
            $this->normalizeStandings($tableFromArray),
        );
    }

    public function test_tied_teams_without_head_to_head_fall_back_to_alphabetical_order(): void
    {
        $this->createTeam('Zeta FC', 'ZET', 85);
        $this->createTeam('Alpha FC', 'ALP', 82);
        $charlie = $this->createTeam('Charlie FC', 'CHA', 80);
        $delta = $this->createTeam('Delta FC', 'DEL', 78);

        $zeta = Team::query()->where('short_name', 'ZET')->firstOrFail();
        $alpha = Team::query()->where('short_name', 'ALP')->firstOrFail();

        $this->createPlayedMatch($zeta, $charlie, 1, 0);
        $this->createPlayedMatch($alpha, $delta, 1, 0);

        $table = (new LeagueTableService())->getTable();

        $zetaStanding = $this->standingByShortName($table, 'ZET');
        $alphaStanding = $this->standingByShortName($table, 'ALP');

        $this->assertSame(3, $zetaStanding->points);
        $this->assertSame(3, $alphaStanding->points);
        $this->assertSame(1, $zetaStanding->goalDifference);
        $this->assertSame(1, $alphaStanding->goalDifference);
        $this->assertSame(1, $zetaStanding->goalsFor);
        $this->assertSame(1, $alphaStanding->goalsFor);
        $this->assertLessThan($zetaStanding->position, $alphaStanding->position);
    }

    public function test_partial_head_to_head_uses_played_leg_for_tiebreaker(): void
    {
        $zeta = $this->createTeam('Zeta FC', 'ZET', 85);
        $alpha = $this->createTeam('Alpha FC', 'ALP', 82);
        $charlie = $this->createTeam('Charlie FC', 'CHA', 80);
        $delta = $this->createTeam('Delta FC', 'DEL', 78);

        $this->createPlayedMatch($zeta, $alpha, 1, 0);
        $this->createPlayedMatch($zeta, $charlie, 0, 0);
        $this->createPlayedMatch($zeta, $delta, 0, 1);
        $this->createPlayedMatch($alpha, $charlie, 1, 0);
        $this->createPlayedMatch($alpha, $delta, 0, 0);

        $table = (new LeagueTableService())->getTable();
        $zetaStanding = $this->standingByShortName($table, 'ZET');
        $alphaStanding = $this->standingByShortName($table, 'ALP');

        $this->assertSame(4, $zetaStanding->points);
        $this->assertSame(4, $alphaStanding->points);
        $this->assertSame(0, $zetaStanding->goalDifference);
        $this->assertSame(0, $alphaStanding->goalDifference);
        $this->assertSame(1, $zetaStanding->goalsFor);
        $this->assertSame(1, $alphaStanding->goalsFor);
        $this->assertLessThan($alphaStanding->position, $zetaStanding->position);
    }

    public function test_get_table_from_results_requires_preloaded_teams(): void
    {
        $this->expectException(LogicException::class);

        (new LeagueTableService())->getTableFromResults([]);
    }

    public function test_positions_are_sequential_with_no_gaps(): void
    {
        $this->createTeam('Real Madrid', 'RMA', 90);
        $this->createTeam('Bayern Munich', 'BAY', 82);
        $this->createTeam('Galatasaray', 'GAL', 72);
        $this->createTeam('Liverpool', 'LIV', 85);

        $table = (new LeagueTableService())->getTable();

        $this->assertSame([1, 2, 3, 4], array_map(
            fn (TeamStanding $standing): int => $standing->position,
            $table
        ));
    }

    public function test_full_six_week_season_produces_valid_final_table(): void
    {
        $this->createTeam('Real Madrid', 'RMA', 90);
        $this->createTeam('Bayern Munich', 'BAY', 82);
        $this->createTeam('Galatasaray', 'GAL', 72);
        $this->createTeam('Liverpool', 'LIV', 85);

        (new FixtureService())->generate();
        (new MatchSimulationService(new FixtureService()))->simulateAllRemainingWeeks();

        $table = (new LeagueTableService())->getTable();

        $this->assertCount(4, $table);
        $this->assertSame([1, 2, 3, 4], array_map(
            fn (TeamStanding $standing): int => $standing->position,
            $table
        ));

        foreach ($table as $standing) {
            $this->assertSame(6, $standing->played);
            $this->assertSame($standing->goalsFor - $standing->goalsAgainst, $standing->goalDifference);
            $this->assertSame(($standing->won * 3) + $standing->drawn, $standing->points);
        }

        $this->assertSame(6, Fixture::query()->where('is_played', true)->count());
        $this->assertSame(12, GameMatch::query()->where('is_played', true)->count());
    }

    private function createTeam(string $name, string $shortName, int $power): Team
    {
        return Team::create([
            'name' => $name,
            'short_name' => $shortName,
            'power' => $power,
            'home_advantage' => 14,
            'goalkeeper_factor' => 14,
            'supporter_strength' => 14,
        ]);
    }

    private function createPlayedMatch(Team $homeTeam, Team $awayTeam, int $homeScore, int $awayScore): void
    {
        $fixture = Fixture::create([
            'week' => $this->weekNumber++,
            'is_played' => true,
        ]);

        GameMatch::create([
            'fixture_id' => $fixture->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'home_score' => $homeScore,
            'away_score' => $awayScore,
            'is_played' => true,
        ]);
    }

    /**
     * @param  array<TeamStanding>  $table
     */
    private function standingByShortName(array $table, string $shortName): TeamStanding
    {
        foreach ($table as $standing) {
            if ($standing->team->short_name === $shortName) {
                return $standing;
            }
        }

        $this->fail("Standing not found for team short name: {$shortName}");
    }

    /**
     * @param  array<TeamStanding>  $table
     * @return array<int, array<string, int|string>>
     */
    private function normalizeStandings(array $table): array
    {
        return array_map(fn (TeamStanding $standing): array => [
            'short_name' => $standing->team->short_name,
            'played' => $standing->played,
            'won' => $standing->won,
            'drawn' => $standing->drawn,
            'lost' => $standing->lost,
            'goals_for' => $standing->goalsFor,
            'goals_against' => $standing->goalsAgainst,
            'goal_difference' => $standing->goalDifference,
            'points' => $standing->points,
            'position' => $standing->position,
        ], $table);
    }
}
