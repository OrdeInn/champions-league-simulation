<?php

namespace Tests\Unit\Services;

use App\Models\Fixture;
use App\Models\GameMatch;
use App\Models\Team;
use App\Services\MatchSimulationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchSimulationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_simulate_match_produces_valid_scores_and_cap(): void
    {
        [$a, $b] = Team::factory()->count(2)->create();
        $match = GameMatch::factory()->create(['home_team_id' => $a->id, 'away_team_id' => $b->id]);

        $simulated = (new MatchSimulationService())->simulateMatch($match);

        $this->assertTrue($simulated->is_played);
        $this->assertGreaterThanOrEqual(0, $simulated->home_score);
        $this->assertGreaterThanOrEqual(0, $simulated->away_score);
        $this->assertLessThanOrEqual(7, $simulated->home_score);
        $this->assertLessThanOrEqual(7, $simulated->away_score);
    }

    public function test_simulate_week_marks_fixture_played_and_skips_already_played_fixture(): void
    {
        [$a, $b, $c, $d] = Team::factory()->count(4)->create();
        $fixture = Fixture::factory()->create(['week' => 1, 'is_played' => false]);
        GameMatch::factory()->create(['fixture_id' => $fixture->id, 'home_team_id' => $a->id, 'away_team_id' => $b->id]);
        GameMatch::factory()->create(['fixture_id' => $fixture->id, 'home_team_id' => $c->id, 'away_team_id' => $d->id]);

        $service = new MatchSimulationService();
        $service->simulateWeek($fixture);
        $this->assertTrue($fixture->refresh()->is_played);

        $snapshot = GameMatch::query()->pluck('home_score', 'id')->all();
        $service->simulateWeek($fixture->refresh());
        $this->assertSame($snapshot, GameMatch::query()->pluck('home_score', 'id')->all());
    }

    public function test_stronger_team_wins_more_often_and_home_advantage_exists(): void
    {
        $strong = Team::factory()->create([
            'power' => 90,
            'home_advantage' => 18,
            'goalkeeper_factor' => 18,
            'supporter_strength' => 18,
        ]);
        $weak = Team::factory()->create([
            'power' => 60,
            'home_advantage' => 5,
            'goalkeeper_factor' => 5,
            'supporter_strength' => 5,
        ]);
        $fixture = Fixture::factory()->create();
        $match = GameMatch::factory()->create(['fixture_id' => $fixture->id, 'home_team_id' => $strong->id, 'away_team_id' => $weak->id]);

        $service = new MatchSimulationService();
        $strongWins = 0;
        $homeWins = 0;
        $awayWins = 0;

        for ($i = 0; $i < 500; $i++) {
            $match->update([
                'home_team_id' => $i % 2 === 0 ? $strong->id : $weak->id,
                'away_team_id' => $i % 2 === 0 ? $weak->id : $strong->id,
                'home_score' => null,
                'away_score' => null,
                'is_played' => false,
            ]);
            $played = $service->simulateMatch($match);
            if ($played->home_score > $played->away_score) {
                $homeWins++;
            } elseif ($played->away_score > $played->home_score) {
                $awayWins++;
            }
            $strongScore = $i % 2 === 0 ? $played->home_score : $played->away_score;
            $weakScore = $i % 2 === 0 ? $played->away_score : $played->home_score;
            if ($strongScore > $weakScore) {
                $strongWins++;
            }
        }

        $this->assertGreaterThan(0.40, $strongWins / 500);
        $this->assertGreaterThan($awayWins, $homeWins);
    }

    public function test_expected_goals_generate_goals_and_reset_and_simulate_all(): void
    {
        $a = Team::factory()->create([
            'power' => 88,
            'goalkeeper_factor' => 16,
            'home_advantage' => 12,
            'supporter_strength' => 14,
        ]);
        $b = Team::factory()->create([
            'power' => 62,
            'goalkeeper_factor' => 8,
            'home_advantage' => 6,
            'supporter_strength' => 7,
        ]);
        $c = Team::factory()->create();
        $d = Team::factory()->create();
        $fixture1 = Fixture::factory()->create(['week' => 1]);
        $fixture2 = Fixture::factory()->create(['week' => 2]);
        GameMatch::factory()->create(['fixture_id' => $fixture1->id, 'home_team_id' => $a->id, 'away_team_id' => $b->id]);
        GameMatch::factory()->create(['fixture_id' => $fixture1->id, 'home_team_id' => $c->id, 'away_team_id' => $d->id]);
        GameMatch::factory()->create(['fixture_id' => $fixture2->id, 'home_team_id' => $a->id, 'away_team_id' => $c->id]);
        GameMatch::factory()->create(['fixture_id' => $fixture2->id, 'home_team_id' => $b->id, 'away_team_id' => $d->id]);

        $service = new MatchSimulationService();
        $this->assertGreaterThan(
            $service->calculateExpectedGoals($b, false, $a),
            $service->calculateExpectedGoals($a, false, $b)
        );

        for ($i = 0; $i < 1000; $i++) {
            $this->assertGreaterThanOrEqual(0, $service->generateGoals(1.2));
            $this->assertLessThanOrEqual(7, $service->generateGoals(5.0));
        }

        $service->simulateAllRemainingWeeks();
        $this->assertSame(2, Fixture::query()->where('is_played', true)->count());

        $service->resetAllResults();
        $this->assertSame(0, Fixture::query()->where('is_played', true)->count());
        $this->assertSame(0, GameMatch::query()->where('is_played', true)->count());
    }
}
