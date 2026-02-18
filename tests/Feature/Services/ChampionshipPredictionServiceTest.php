<?php

namespace Tests\Feature\Services;

use App\DataTransferObjects\TeamPrediction;
use App\Models\Fixture;
use App\Models\GameMatch;
use App\Models\Team;
use App\Services\ChampionshipPredictionService;
use App\Services\FixtureService;
use App\Services\LeagueTableService;
use App\Services\MatchSimulationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChampionshipPredictionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_null_when_only_three_weeks_played(): void
    {
        $this->createBalancedTeams();
        (new FixtureService())->generate();
        $this->playWeeks(3);

        $service = $this->makeService(iterations: 300);

        $this->assertFalse($service->shouldShowPredictions());
        $this->assertNull($service->getPredictions());
    }

    public function test_returns_predictions_when_four_weeks_played(): void
    {
        $this->createBalancedTeams();
        (new FixtureService())->generate();
        $this->playWeeks(4);

        $predictions = $this->makeService(iterations: 500)->getPredictions();

        $this->assertNotNull($predictions);
        $this->assertCount(4, $predictions);
        $this->assertContainsOnlyInstancesOf(TeamPrediction::class, $predictions);
    }

    public function test_probabilities_sum_to_approximately_hundred_percent(): void
    {
        $this->createBalancedTeams();
        (new FixtureService())->generate();
        $this->playWeeks(4);

        $predictions = $this->makeService(iterations: 700)->getPredictions();
        $sum = array_sum(array_map(fn (TeamPrediction $prediction): float => $prediction->probability, $predictions ?? []));

        $this->assertGreaterThanOrEqual(99.5, $sum);
        $this->assertLessThanOrEqual(100.5, $sum);
    }

    public function test_large_leader_after_week_four_gets_near_certain_probability(): void
    {
        $teams = $this->createBalancedTeams();
        (new FixtureService())->generate();
        $this->setWeeksUpToFourWithDominantLeader($teams[0]);

        $predictions = $this->makeService(iterations: 600)->getPredictions();
        $leaderPrediction = $this->predictionByShortName($predictions ?? [], $teams[0]->short_name);

        $this->assertGreaterThanOrEqual(99.0, $leaderPrediction->probability);
    }

    public function test_after_all_weeks_played_winner_is_exactly_hundred_percent(): void
    {
        $this->createBalancedTeams();
        (new FixtureService())->generate();
        (new MatchSimulationService(new FixtureService(), seed: 42))->simulateAllRemainingWeeks();

        $predictions = $this->makeService(iterations: 500)->getPredictions();

        $this->assertNotNull($predictions);

        $winnerPrediction = $predictions[0];
        $this->assertSame(100.0, $winnerPrediction->probability);

        foreach (array_slice($predictions, 1) as $prediction) {
            $this->assertSame(0.0, $prediction->probability);
        }
    }

    public function test_monte_carlo_does_not_modify_database_records(): void
    {
        $this->createBalancedTeams();
        (new FixtureService())->generate();
        $this->playWeeks(4);

        $before = GameMatch::query()
            ->orderBy('id')
            ->get(['id', 'is_played', 'home_score', 'away_score'])
            ->toArray();

        $this->makeService(iterations: 500)->getPredictions();

        $after = GameMatch::query()
            ->orderBy('id')
            ->get(['id', 'is_played', 'home_score', 'away_score'])
            ->toArray();

        $this->assertSame($before, $after);
    }

    public function test_simulate_remaining_matches_returns_expected_structure(): void
    {
        $this->createBalancedTeams();
        (new FixtureService())->generate();
        $this->playWeeks(4);

        $remainingMatches = GameMatch::query()
            ->where('is_played', false)
            ->with('homeTeam', 'awayTeam')
            ->get();

        $results = $this->makeService(iterations: 200)->simulateRemainingMatches($remainingMatches);

        $this->assertCount($remainingMatches->count(), $results);

        foreach ($results as $matchId => $result) {
            $this->assertArrayHasKey('match_id', $result);
            $this->assertArrayHasKey('home_team_id', $result);
            $this->assertArrayHasKey('away_team_id', $result);
            $this->assertArrayHasKey('home_score', $result);
            $this->assertArrayHasKey('away_score', $result);
            $this->assertSame($matchId, $result['match_id']);
            $this->assertGreaterThanOrEqual(0, $result['home_score']);
            $this->assertGreaterThanOrEqual(0, $result['away_score']);
            $this->assertLessThanOrEqual(7, $result['home_score']);
            $this->assertLessThanOrEqual(7, $result['away_score']);
        }
    }

    public function test_equal_strength_and_equal_table_produces_roughly_balanced_probabilities(): void
    {
        $this->createBalancedTeams();
        (new FixtureService())->generate();
        $this->setWeeksUpToFourAsDraws();

        $predictions = $this->makeService(iterations: 1200)->getPredictions();
        $probabilities = array_map(fn (TeamPrediction $prediction): float => $prediction->probability, $predictions ?? []);

        $this->assertNotEmpty($probabilities);
        $this->assertLessThanOrEqual(25.0, max($probabilities) - min($probabilities));
    }

    /**
     * @return array<int, Team>
     */
    private function createBalancedTeams(): array
    {
        return [
            Team::create([
                'name' => 'Alpha FC',
                'short_name' => 'ALP',
                'power' => 82,
                'home_advantage' => 14,
                'goalkeeper_factor' => 14,
                'supporter_strength' => 14,
            ]),
            Team::create([
                'name' => 'Bravo FC',
                'short_name' => 'BRV',
                'power' => 82,
                'home_advantage' => 14,
                'goalkeeper_factor' => 14,
                'supporter_strength' => 14,
            ]),
            Team::create([
                'name' => 'Charlie FC',
                'short_name' => 'CHA',
                'power' => 82,
                'home_advantage' => 14,
                'goalkeeper_factor' => 14,
                'supporter_strength' => 14,
            ]),
            Team::create([
                'name' => 'Delta FC',
                'short_name' => 'DEL',
                'power' => 82,
                'home_advantage' => 14,
                'goalkeeper_factor' => 14,
                'supporter_strength' => 14,
            ]),
        ];
    }

    private function playWeeks(int $weeks): void
    {
        $fixtures = Fixture::query()->orderBy('week')->get();

        foreach ($fixtures as $fixture) {
            if ($fixture->week <= $weeks) {
                foreach ($fixture->matches as $match) {
                    $match->update([
                        'home_score' => 1,
                        'away_score' => 1,
                        'is_played' => true,
                    ]);
                }

                $fixture->update(['is_played' => true]);
            }
        }
    }

    private function setWeeksUpToFourWithDominantLeader(Team $leader): void
    {
        $fixtures = Fixture::query()->with('matches')->orderBy('week')->get();

        foreach ($fixtures as $fixture) {
            if ($fixture->week <= 4) {
                foreach ($fixture->matches as $match) {
                    $isLeaderHome = (int) $match->home_team_id === (int) $leader->id;
                    $isLeaderAway = (int) $match->away_team_id === (int) $leader->id;

                    if ($isLeaderHome) {
                        $match->update([
                            'home_score' => 3,
                            'away_score' => 0,
                            'is_played' => true,
                        ]);
                    } elseif ($isLeaderAway) {
                        $match->update([
                            'home_score' => 0,
                            'away_score' => 3,
                            'is_played' => true,
                        ]);
                    } else {
                        $match->update([
                            'home_score' => 0,
                            'away_score' => 0,
                            'is_played' => true,
                        ]);
                    }
                }

                $fixture->update(['is_played' => true]);
            }
        }
    }

    private function setWeeksUpToFourAsDraws(): void
    {
        $fixtures = Fixture::query()->with('matches')->orderBy('week')->get();

        foreach ($fixtures as $fixture) {
            if ($fixture->week <= 4) {
                foreach ($fixture->matches as $match) {
                    $match->update([
                        'home_score' => 0,
                        'away_score' => 0,
                        'is_played' => true,
                    ]);
                }

                $fixture->update(['is_played' => true]);
            }
        }
    }

    /**
     * @param  array<TeamPrediction>  $predictions
     */
    private function predictionByShortName(array $predictions, string $shortName): TeamPrediction
    {
        foreach ($predictions as $prediction) {
            if ($prediction->team->short_name === $shortName) {
                return $prediction;
            }
        }

        $this->fail("Prediction not found for team short name: {$shortName}");
    }

    private function makeService(int $iterations): ChampionshipPredictionService
    {
        return new ChampionshipPredictionService(
            leagueTableService: new LeagueTableService(),
            matchSimulationService: new MatchSimulationService(new FixtureService()),
            iterations: $iterations
        );
    }
}
