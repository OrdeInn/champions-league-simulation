<?php

namespace App\Services;

use App\Models\Fixture;
use App\Models\GameMatch;
use App\Models\Team;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FixtureService
{
    /**
     * @return Collection<Fixture>
     */
    public function generate(): Collection
    {
        $teams = Team::orderBy('id')->get();

        if ($teams->count() !== 4) {
            throw new InvalidArgumentException(
                "Fixture generation requires exactly 4 teams, but {$teams->count()} team(s) found."
            );
        }

        return DB::transaction(function () use ($teams) {
            $this->clearExistingFixtures();

            $weeklyPairings = $this->generateRoundRobinPairings($teams);

            foreach ($weeklyPairings as $weekNumber => $pairs) {
                $fixture = Fixture::create([
                    'week' => $weekNumber + 1,
                    'is_played' => false,
                ]);

                foreach ($pairs as [$homeTeam, $awayTeam]) {
                    GameMatch::create([
                        'fixture_id' => $fixture->id,
                        'home_team_id' => $homeTeam->id,
                        'away_team_id' => $awayTeam->id,
                        'home_score' => null,
                        'away_score' => null,
                        'is_played' => false,
                    ]);
                }
            }

            return Fixture::with('matches.homeTeam', 'matches.awayTeam')
                ->orderBy('week')
                ->get();
        });
    }

    /**
     * Pure logic: produces an array of 6 weeks, each containing an array of [homeTeam, awayTeam] pairs.
     * No database interaction.
     *
     * Uses the round-robin circle method: fix the first team, rotate the remaining three.
     * Weeks 4-6 mirror weeks 1-3 with home/away swapped.
     *
     * @param  Collection<Team>  $teams  Must contain exactly 4 teams ordered by id
     * @return array Array of 6 weeks, each week is array of [homeTeam, awayTeam] pairs
     */
    public function generateRoundRobinPairings(Collection $teams): array
    {
        if ($teams->count() !== 4) {
            throw new InvalidArgumentException(
                "generateRoundRobinPairings requires exactly 4 teams, but {$teams->count()} team(s) provided."
            );
        }

        $teamArray = $teams->values()->all();

        // Fixed team at position 0; rotating positions 1, 2, 3
        $fixed = $teamArray[0];
        $rotating = [$teamArray[1], $teamArray[2], $teamArray[3]];

        $weeks = [];

        // Generate 3 weeks (single round-robin).
        // Note: this produces a valid schedule, but the match order within each week
        // may differ from the illustrative example in the ticket spec. All required
        // invariants hold: each pair meets exactly once per half, with reversed home/away.
        for ($round = 0; $round < 3; $round++) {
            // Circle method pairing:
            // pos0 (fixed) vs pos1 (first rotating)
            // pos3 (last rotating) vs pos2 (middle rotating)
            $weeks[] = [
                [$fixed, $rotating[0]],
                [$rotating[2], $rotating[1]],
            ];

            // Rotate: move last element to front
            $rotating = [$rotating[2], $rotating[0], $rotating[1]];
        }

        // Weeks 4-6: reverse home/away of weeks 1-3
        for ($round = 0; $round < 3; $round++) {
            $reversedWeek = [];
            foreach ($weeks[$round] as [$homeTeam, $awayTeam]) {
                $reversedWeek[] = [$awayTeam, $homeTeam];
            }
            $weeks[] = $reversedWeek;
        }

        return $weeks;
    }

    public function clearExistingFixtures(): void
    {
        GameMatch::query()->delete();
        Fixture::query()->delete();
    }
}
