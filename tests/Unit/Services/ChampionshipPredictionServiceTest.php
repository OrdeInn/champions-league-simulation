<?php

namespace Tests\Unit\Services;

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

    public function test_returns_null_before_week_four_and_predictions_after(): void
    {
        Team::factory()->count(4)->create();
        (new FixtureService())->generate();
        $this->markPlayedWeeks(3);

        $service = $this->service(400);
        $this->assertNull($service->getPredictions());

        $this->markPlayedWeeks(4);
        $predictions = $service->getPredictions();
        $this->assertCount(4, $predictions ?? []);
        $this->assertContainsOnlyInstancesOf(TeamPrediction::class, $predictions);
    }

    public function test_probability_sum_and_equal_distribution_and_no_persist(): void
    {
        Team::factory()->count(4)->create([
            'power' => 80,
            'home_advantage' => 14,
            'goalkeeper_factor' => 14,
            'supporter_strength' => 14,
        ]);
        (new FixtureService())->generate();
        $this->markDrawWeeks(4);

        $before = GameMatch::query()->orderBy('id')->get(['id','is_played','home_score','away_score'])->toArray();
        $predictions = $this->service(900)->getPredictions();
        $after = GameMatch::query()->orderBy('id')->get(['id','is_played','home_score','away_score'])->toArray();

        $sum = array_sum(array_map(fn (TeamPrediction $p): float => $p->probability, $predictions ?? []));
        $this->assertGreaterThanOrEqual(99.5, $sum);
        $this->assertLessThanOrEqual(100.5, $sum);
        $this->assertSame($before, $after);

        $probs = array_map(fn (TeamPrediction $p): float => $p->probability, $predictions ?? []);
        $this->assertLessThanOrEqual(30.0, max($probs) - min($probs));
    }

    public function test_dominant_leader_and_completed_season_paths(): void
    {
        $teams = Team::factory()->count(4)->create();
        (new FixtureService())->generate();

        // Make first team dominant through week 4
        foreach (Fixture::with('matches')->where('week', '<=', 4)->get() as $fixture) {
            foreach ($fixture->matches as $match) {
                if ($match->home_team_id === $teams[0]->id) {
                    $match->update(['home_score' => 3, 'away_score' => 0, 'is_played' => true]);
                } elseif ($match->away_team_id === $teams[0]->id) {
                    $match->update(['home_score' => 0, 'away_score' => 3, 'is_played' => true]);
                } else {
                    $match->update(['home_score' => 0, 'away_score' => 0, 'is_played' => true]);
                }
            }
            $fixture->update(['is_played' => true]);
        }

        $leaderPrediction = $this->findByTeamId($this->service(500)->getPredictions() ?? [], $teams[0]->id);
        $this->assertGreaterThanOrEqual(95.0, $leaderPrediction->probability);

        (new MatchSimulationService(seed: 42))->simulateAllRemainingWeeks();
        $final = $this->service(300)->getPredictions();
        $this->assertSame(100.0, $final[0]->probability);
    }

    /** @param array<TeamPrediction> $predictions */
    private function findByTeamId(array $predictions, int $teamId): TeamPrediction
    {
        foreach ($predictions as $prediction) {
            if ($prediction->team->id === $teamId) {
                return $prediction;
            }
        }

        $this->fail('prediction not found');
    }

    private function service(int $iterations): ChampionshipPredictionService
    {
        return new ChampionshipPredictionService(new LeagueTableService(), new MatchSimulationService(), $iterations);
    }

    private function markPlayedWeeks(int $week): void
    {
        foreach (Fixture::with('matches')->where('week', '<=', $week)->get() as $fixture) {
            foreach ($fixture->matches as $match) {
                $match->update(['home_score' => 1, 'away_score' => 1, 'is_played' => true]);
            }
            $fixture->update(['is_played' => true]);
        }
    }

    private function markDrawWeeks(int $week): void
    {
        foreach (Fixture::with('matches')->where('week', '<=', $week)->get() as $fixture) {
            foreach ($fixture->matches as $match) {
                $match->update(['home_score' => 0, 'away_score' => 0, 'is_played' => true]);
            }
            $fixture->update(['is_played' => true]);
        }
    }
}
