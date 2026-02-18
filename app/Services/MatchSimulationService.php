<?php

namespace App\Services;

use App\Models\Fixture;
use App\Models\GameMatch;
use App\Models\Team;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MatchSimulationService
{
    public function __construct(
        private readonly FixtureService $fixtureService,
        private readonly ?int $seed = null
    ) {}

    public function simulateWeek(Fixture $fixture): Fixture
    {
        return DB::transaction(function () use ($fixture): Fixture {
            $fixture = Fixture::query()
                ->with('matches.homeTeam', 'matches.awayTeam')
                ->lockForUpdate()
                ->findOrFail($fixture->id);

            if ($fixture->is_played) {
                return $fixture;
            }

            foreach ($fixture->matches as $match) {
                if (! $match->is_played) {
                    $this->simulateMatch($match);
                }
            }

            $fixture->update([
                'is_played' => $fixture->matches->every(fn (GameMatch $match): bool => $match->is_played),
            ]);

            return $fixture->fresh()->load('matches.homeTeam', 'matches.awayTeam');
        });
    }

    /**
     * @return Collection<Fixture>
     */
    public function simulateAllRemainingWeeks(): Collection
    {
        $fixtures = Fixture::query()
            ->unplayed()
            ->orderBy('week')
            ->get();

        return $fixtures->map(fn (Fixture $fixture): Fixture => $this->simulateWeek($fixture));
    }

    public function simulateMatch(GameMatch $match): GameMatch
    {
        if ($match->is_played) {
            return $match;
        }

        $match->load('homeTeam', 'awayTeam');

        if ($this->seed !== null) {
            mt_srand($this->seed);
        }

        $homeExpectedGoals = $this->calculateExpectedGoals($match->homeTeam, true, $match->awayTeam);
        $awayExpectedGoals = $this->calculateExpectedGoals($match->awayTeam, false, $match->homeTeam);

        $match->update([
            'home_score' => $this->generateGoals($homeExpectedGoals),
            'away_score' => $this->generateGoals($awayExpectedGoals),
            'is_played' => true,
        ]);

        return $match;
    }

    /**
     * Estimates goals from team attack, opponent defensive weakness, and home-context boosts.
     * Output is bounded to keep simulated scores in a realistic range.
     */
    public function calculateExpectedGoals(Team $team, bool $isHome, Team $opponent): float
    {
        $baseRate = 1.5;
        // Normalize team strength into a multiplier around 1.0.
        $attackStrength = $team->power / 100;
        // Higher goalkeeper factor reduces opponent scoring expectation.
        $defenseWeakness = 1 - ($opponent->goalkeeper_factor / 20);

        $homeBoost = 0.0;
        $supporterBoost = 0.0;

        if ($isHome) {
            // Home and crowd effects are modeled as small additive lifts.
            $homeBoost = ($team->home_advantage / 20) * 0.3;
            $supporterBoost = ($team->supporter_strength / 20) * 0.15;
        }

        $expectedGoals = ($baseRate * $attackStrength * (0.5 + ($defenseWeakness * 0.5))) + $homeBoost + $supporterBoost;

        // Clamp to avoid unrealistic extremes and keep simulation stable.
        return max(0.3, min(3.5, $expectedGoals));
    }

    /**
     * Samples a goal count by walking the cumulative Poisson probabilities (inverse CDF).
     */
    public function generateGoals(float $expectedGoals): int
    {
        $expectedGoals = max(0.3, $expectedGoals);
        // Uniform random draw used to pick a bucket in the cumulative distribution.
        $random = mt_rand() / mt_getrandmax();
        $cumulative = 0.0;

        for ($goals = 0; $goals <= 7; $goals++) {
            $cumulative += $this->poissonProbability($expectedGoals, $goals);

            if ($random <= $cumulative) {
                return $goals;
            }
        }

        // Any remaining probability mass is folded into the max modeled bucket.
        return 7;
    }

    public function resetSimulation(): void
    {
        DB::transaction(function (): void {
            $this->fixtureService->clearExistingFixtures();
        });
    }

    /**
     * Poisson PMF: P(X = k | lambda).
     */
    private function poissonProbability(float $lambda, int $k): float
    {
        return exp(-$lambda) * (($lambda ** $k) / $this->factorial($k));
    }

    private function factorial(int $number): int
    {
        if ($number <= 1) {
            return 1;
        }

        $result = 1;

        for ($i = 2; $i <= $number; $i++) {
            // Iterative factorial keeps this fast and avoids recursion overhead.
            $result *= $i;
        }

        return $result;
    }
}
