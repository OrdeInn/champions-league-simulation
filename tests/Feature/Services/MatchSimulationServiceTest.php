<?php

namespace Tests\Feature\Services;

use App\Models\Fixture;
use App\Models\GameMatch;
use App\Models\Team;
use App\Services\MatchSimulationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchSimulationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        mt_srand();
        parent::tearDown();
    }

    public function test_simulate_match_produces_valid_integer_scores(): void
    {
        [$homeTeam, $awayTeam] = $this->createTeams();
        $fixture = Fixture::create(['week' => 1, 'is_played' => false]);
        $match = GameMatch::create([
            'fixture_id' => $fixture->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'home_score' => null,
            'away_score' => null,
            'is_played' => false,
        ]);

        $simulatedMatch = (new MatchSimulationService())->simulateMatch($match);

        $this->assertTrue($simulatedMatch->is_played);
        $this->assertIsInt($simulatedMatch->home_score);
        $this->assertIsInt($simulatedMatch->away_score);
        $this->assertGreaterThanOrEqual(0, $simulatedMatch->home_score);
        $this->assertGreaterThanOrEqual(0, $simulatedMatch->away_score);
    }

    public function test_simulate_week_marks_matches_and_fixture_as_played(): void
    {
        [$teamA, $teamB, $teamC, $teamD] = $this->createTeams();
        $fixture = Fixture::create(['week' => 1, 'is_played' => false]);

        GameMatch::create([
            'fixture_id' => $fixture->id,
            'home_team_id' => $teamA->id,
            'away_team_id' => $teamB->id,
            'is_played' => false,
        ]);

        GameMatch::create([
            'fixture_id' => $fixture->id,
            'home_team_id' => $teamC->id,
            'away_team_id' => $teamD->id,
            'is_played' => false,
        ]);

        $updatedFixture = (new MatchSimulationService())->simulateWeek($fixture);

        $this->assertTrue($updatedFixture->is_played);
        $this->assertCount(2, $updatedFixture->matches);
        $this->assertTrue($updatedFixture->matches->every(fn (GameMatch $match): bool => $match->is_played));
    }

    public function test_already_played_fixture_is_skipped(): void
    {
        [$homeTeam, $awayTeam] = $this->createTeams();
        $fixture = Fixture::create(['week' => 1, 'is_played' => true]);
        $match = GameMatch::create([
            'fixture_id' => $fixture->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'home_score' => 2,
            'away_score' => 1,
            'is_played' => true,
        ]);

        $simulatedFixture = (new MatchSimulationService(seed: 42))->simulateWeek($fixture);

        $reloadedMatch = $match->refresh();
        $this->assertTrue($simulatedFixture->is_played);
        $this->assertSame(2, $reloadedMatch->home_score);
        $this->assertSame(1, $reloadedMatch->away_score);
    }

    public function test_simulate_all_remaining_weeks_processes_in_week_order(): void
    {
        [$teamA, $teamB, $teamC, $teamD] = $this->createTeams();

        $fixtureWeek3 = Fixture::create(['week' => 3, 'is_played' => false]);
        $fixtureWeek1 = Fixture::create(['week' => 1, 'is_played' => false]);
        $fixtureWeek2 = Fixture::create(['week' => 2, 'is_played' => false]);

        GameMatch::create([
            'fixture_id' => $fixtureWeek3->id,
            'home_team_id' => $teamA->id,
            'away_team_id' => $teamB->id,
            'is_played' => false,
        ]);

        GameMatch::create([
            'fixture_id' => $fixtureWeek1->id,
            'home_team_id' => $teamC->id,
            'away_team_id' => $teamD->id,
            'is_played' => false,
        ]);

        GameMatch::create([
            'fixture_id' => $fixtureWeek2->id,
            'home_team_id' => $teamA->id,
            'away_team_id' => $teamC->id,
            'is_played' => false,
        ]);

        $fixtures = (new MatchSimulationService())->simulateAllRemainingWeeks();

        $this->assertSame([1, 2, 3], $fixtures->pluck('week')->all());
        $this->assertTrue($fixtures->every(fn (Fixture $fixture): bool => $fixture->is_played));
    }

    public function test_simulate_all_remaining_weeks_returns_empty_collection_when_nothing_to_play(): void
    {
        Fixture::create(['week' => 1, 'is_played' => true]);
        Fixture::create(['week' => 2, 'is_played' => true]);

        $fixtures = (new MatchSimulationService())->simulateAllRemainingWeeks();

        $this->assertTrue($fixtures->isEmpty());
    }

    public function test_reset_all_results_clears_scores_and_flags(): void
    {
        [$teamA, $teamB, $teamC] = $this->createTeams();
        $fixture = Fixture::create(['week' => 1, 'is_played' => true]);

        GameMatch::create([
            'fixture_id' => $fixture->id,
            'home_team_id' => $teamA->id,
            'away_team_id' => $teamB->id,
            'home_score' => 3,
            'away_score' => 1,
            'is_played' => true,
        ]);

        GameMatch::create([
            'fixture_id' => $fixture->id,
            'home_team_id' => $teamC->id,
            'away_team_id' => $teamA->id,
            'home_score' => 0,
            'away_score' => 0,
            'is_played' => true,
        ]);

        (new MatchSimulationService())->resetAllResults();

        $this->assertFalse($fixture->refresh()->is_played);
        $this->assertSame(0, GameMatch::query()->where('is_played', true)->count());
        $this->assertSame(2, GameMatch::query()->whereNull('home_score')->whereNull('away_score')->count());
    }

    public function test_stronger_team_wins_more_frequently_over_many_simulations(): void
    {
        $strongTeam = Team::create([
            'name' => 'Strong Team',
            'short_name' => 'STG',
            'power' => 90,
            'home_advantage' => 18,
            'goalkeeper_factor' => 18,
            'supporter_strength' => 18,
        ]);

        $weakTeam = Team::create([
            'name' => 'Weak Team',
            'short_name' => 'WEK',
            'power' => 72,
            'home_advantage' => 10,
            'goalkeeper_factor' => 10,
            'supporter_strength' => 10,
        ]);

        $fixture = Fixture::create(['week' => 1, 'is_played' => false]);
        $match = GameMatch::create([
            'fixture_id' => $fixture->id,
            'home_team_id' => $strongTeam->id,
            'away_team_id' => $weakTeam->id,
            'is_played' => false,
        ]);

        $service = new MatchSimulationService(seed: null);
        $strongWins = 0;
        $iterations = 3000;

        for ($i = 0; $i < $iterations; $i++) {
            $isStrongHome = $i % 2 === 0;

            $match->update([
                'home_team_id' => $isStrongHome ? $strongTeam->id : $weakTeam->id,
                'away_team_id' => $isStrongHome ? $weakTeam->id : $strongTeam->id,
                'home_score' => null,
                'away_score' => null,
                'is_played' => false,
            ]);

            $simulated = $service->simulateMatch($match);
            $strongScore = $isStrongHome ? $simulated->home_score : $simulated->away_score;
            $weakScore = $isStrongHome ? $simulated->away_score : $simulated->home_score;

            if ($strongScore > $weakScore) {
                $strongWins++;
            }
        }

        $winRate = $strongWins / $iterations;

        $this->assertGreaterThanOrEqual(0.45, $winRate);
        $this->assertLessThanOrEqual(0.75, $winRate);
    }

    public function test_home_team_wins_more_than_away_team_over_many_simulations(): void
    {
        $teamA = Team::create([
            'name' => 'Home Biased A',
            'short_name' => 'HBA',
            'power' => 80,
            'home_advantage' => 16,
            'goalkeeper_factor' => 14,
            'supporter_strength' => 16,
        ]);

        $teamB = Team::create([
            'name' => 'Home Biased B',
            'short_name' => 'HBB',
            'power' => 80,
            'home_advantage' => 16,
            'goalkeeper_factor' => 14,
            'supporter_strength' => 16,
        ]);

        $fixture = Fixture::create(['week' => 1, 'is_played' => false]);
        $match = GameMatch::create([
            'fixture_id' => $fixture->id,
            'home_team_id' => $teamA->id,
            'away_team_id' => $teamB->id,
            'is_played' => false,
        ]);

        $service = new MatchSimulationService(seed: null);
        $homeWins = 0;
        $awayWins = 0;
        $iterations = 1000;

        for ($i = 0; $i < $iterations; $i++) {
            $match->update([
                'home_score' => null,
                'away_score' => null,
                'is_played' => false,
            ]);

            $simulated = $service->simulateMatch($match);

            if ($simulated->home_score > $simulated->away_score) {
                $homeWins++;
            } elseif ($simulated->away_score > $simulated->home_score) {
                $awayWins++;
            }
        }

        $this->assertGreaterThan($awayWins, $homeWins);
    }

    public function test_calculate_expected_goals_returns_higher_for_stronger_team(): void
    {
        $opponent = Team::create([
            'name' => 'Opponent',
            'short_name' => 'OPP',
            'power' => 80,
            'home_advantage' => 12,
            'goalkeeper_factor' => 15,
            'supporter_strength' => 12,
        ]);

        $strongTeam = Team::create([
            'name' => 'Expected Goals Strong',
            'short_name' => 'EGS',
            'power' => 90,
            'home_advantage' => 12,
            'goalkeeper_factor' => 14,
            'supporter_strength' => 12,
        ]);

        $weakTeam = Team::create([
            'name' => 'Expected Goals Weak',
            'short_name' => 'EGW',
            'power' => 72,
            'home_advantage' => 12,
            'goalkeeper_factor' => 14,
            'supporter_strength' => 12,
        ]);

        $service = new MatchSimulationService();

        $strongExpectedGoals = $service->calculateExpectedGoals($strongTeam, false, $opponent);
        $weakExpectedGoals = $service->calculateExpectedGoals($weakTeam, false, $opponent);

        $this->assertGreaterThan($weakExpectedGoals, $strongExpectedGoals);
    }

    public function test_generate_goals_never_returns_negative_numbers(): void
    {
        $service = new MatchSimulationService(seed: 123);

        for ($i = 0; $i < 250; $i++) {
            $goals = $service->generateGoals(1.7);
            $this->assertGreaterThanOrEqual(0, $goals);
        }
    }

    public function test_generate_goals_is_capped_at_seven(): void
    {
        $service = new MatchSimulationService(seed: 321);

        for ($i = 0; $i < 500; $i++) {
            $goals = $service->generateGoals(10.0);
            $this->assertLessThanOrEqual(7, $goals);
        }
    }

    public function test_seeded_rng_produces_repeatable_results_for_same_match_context(): void
    {
        [$homeTeam, $awayTeam] = $this->createTeams();
        $fixture = Fixture::create(['week' => 1, 'is_played' => false]);
        $match = GameMatch::create([
            'fixture_id' => $fixture->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'is_played' => false,
        ]);

        $service = new MatchSimulationService(seed: 42);
        $firstResult = $service->simulateMatch($match);

        $homeScore = $firstResult->home_score;
        $awayScore = $firstResult->away_score;

        $match->update([
            'home_score' => null,
            'away_score' => null,
            'is_played' => false,
        ]);

        $secondResult = $service->simulateMatch($match);

        $this->assertSame($homeScore, $secondResult->home_score);
        $this->assertSame($awayScore, $secondResult->away_score);
    }

    public function test_simulate_week_only_simulates_unplayed_matches_and_preserves_played_match(): void
    {
        [$teamA, $teamB, $teamC, $teamD] = $this->createTeams();
        $fixture = Fixture::create(['week' => 1, 'is_played' => false]);

        GameMatch::create([
            'fixture_id' => $fixture->id,
            'home_team_id' => $teamA->id,
            'away_team_id' => $teamB->id,
            'home_score' => 1,
            'away_score' => 1,
            'is_played' => true,
        ]);

        GameMatch::create([
            'fixture_id' => $fixture->id,
            'home_team_id' => $teamC->id,
            'away_team_id' => $teamD->id,
            'is_played' => false,
        ]);

        $simulated = (new MatchSimulationService())->simulateWeek($fixture)->refresh()->load('matches');
        $keptMatch = $simulated->matches->firstWhere('home_team_id', $teamA->id);

        $this->assertSame(1, $keptMatch->home_score);
        $this->assertSame(1, $keptMatch->away_score);
        $this->assertTrue($simulated->is_played);
    }

    /**
     * @return array<int, Team>
     */
    private function createTeams(): array
    {
        return [
            Team::create([
                'name' => 'Team Alpha',
                'short_name' => 'TAL',
                'power' => 88,
                'home_advantage' => 15,
                'goalkeeper_factor' => 16,
                'supporter_strength' => 15,
            ]),
            Team::create([
                'name' => 'Team Bravo',
                'short_name' => 'TBR',
                'power' => 80,
                'home_advantage' => 14,
                'goalkeeper_factor' => 14,
                'supporter_strength' => 14,
            ]),
            Team::create([
                'name' => 'Team Charlie',
                'short_name' => 'TCH',
                'power' => 77,
                'home_advantage' => 13,
                'goalkeeper_factor' => 13,
                'supporter_strength' => 13,
            ]),
            Team::create([
                'name' => 'Team Delta',
                'short_name' => 'TDE',
                'power' => 74,
                'home_advantage' => 12,
                'goalkeeper_factor' => 12,
                'supporter_strength' => 12,
            ]),
        ];
    }
}
