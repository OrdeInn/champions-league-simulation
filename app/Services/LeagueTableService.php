<?php

namespace App\Services;

use App\DataTransferObjects\TeamStanding;
use App\Models\GameMatch;
use App\Models\Team;
use Illuminate\Support\Collection;
use LogicException;

class LeagueTableService
{
    private ?Collection $teams = null;

    /**
     * @var array<int, array{home_team_id:int, away_team_id:int, home_score:int, away_score:int}>
     */
    private array $sortingResults = [];

    public function setTeams(Collection $teams): self
    {
        $this->teams = $teams->keyBy('id');

        return $this;
    }

    /**
     * @return array<TeamStanding>
     */
    public function getTable(): array
    {
        $teams = Team::query()->orderBy('name')->get()->keyBy('id');
        $this->setTeams($teams);

        $playedMatches = GameMatch::query()
            ->where('is_played', true)
            ->get(['home_team_id', 'away_team_id', 'home_score', 'away_score']);

        $this->sortingResults = $playedMatches
            ->map(fn (GameMatch $match): array => [
                'home_team_id' => (int) $match->home_team_id,
                'away_team_id' => (int) $match->away_team_id,
                'home_score' => (int) $match->home_score,
                'away_score' => (int) $match->away_score,
            ])
            ->all();

        $standings = $teams
            ->map(fn (Team $team): TeamStanding => $this->calculateStanding($team, $playedMatches))
            ->all();

        return $this->sortStandings($standings);
    }

    /**
     * @param  array<int, array{home_team_id:int, away_team_id:int, home_score:int, away_score:int}>  $results
     * @return array<TeamStanding>
     */
    public function getTableFromResults(array $results): array
    {
        if ($this->teams === null) {
            throw new LogicException('Teams must be preloaded via setTeams() before calling getTableFromResults().');
        }

        $this->sortingResults = array_values(array_map(
            fn (array $result): array => [
                'home_team_id' => (int) $result['home_team_id'],
                'away_team_id' => (int) $result['away_team_id'],
                'home_score' => (int) $result['home_score'],
                'away_score' => (int) $result['away_score'],
            ],
            $results
        ));

        $standings = $this->teams
            ->map(fn (Team $team): TeamStanding => $this->calculateStandingFromArray($team, $this->sortingResults))
            ->all();

        return $this->sortStandings($standings);
    }

    public function calculateStanding(Team $team, Collection $matches): TeamStanding
    {
        $teamId = (int) $team->id;

        $teamMatches = $matches->filter(
            fn (GameMatch $match): bool => (int) $match->home_team_id === $teamId || (int) $match->away_team_id === $teamId
        );

        $stats = [
            'played' => 0,
            'won' => 0,
            'drawn' => 0,
            'lost' => 0,
            'goals_for' => 0,
            'goals_against' => 0,
            'points' => 0,
        ];

        foreach ($teamMatches as $match) {
            $this->applyResultToStats(
                $stats,
                (int) $match->home_team_id === $teamId,
                (int) $match->home_score,
                (int) $match->away_score,
            );
        }

        return new TeamStanding(
            team: $team,
            played: $stats['played'],
            won: $stats['won'],
            drawn: $stats['drawn'],
            lost: $stats['lost'],
            goalsFor: $stats['goals_for'],
            goalsAgainst: $stats['goals_against'],
            goalDifference: $stats['goals_for'] - $stats['goals_against'],
            points: $stats['points'],
            position: 0,
        );
    }

    /**
     * @param  array<int, array{home_team_id:int, away_team_id:int, home_score:int, away_score:int}>  $results
     */
    public function calculateStandingFromArray(Team $team, array $results): TeamStanding
    {
        $teamId = (int) $team->id;
        $stats = [
            'played' => 0,
            'won' => 0,
            'drawn' => 0,
            'lost' => 0,
            'goals_for' => 0,
            'goals_against' => 0,
            'points' => 0,
        ];

        foreach ($results as $result) {
            if ((int) $result['home_team_id'] !== $teamId && (int) $result['away_team_id'] !== $teamId) {
                continue;
            }

            $this->applyResultToStats(
                $stats,
                (int) $result['home_team_id'] === $teamId,
                $result['home_score'],
                $result['away_score'],
            );
        }

        return new TeamStanding(
            team: $team,
            played: $stats['played'],
            won: $stats['won'],
            drawn: $stats['drawn'],
            lost: $stats['lost'],
            goalsFor: $stats['goals_for'],
            goalsAgainst: $stats['goals_against'],
            goalDifference: $stats['goals_for'] - $stats['goals_against'],
            points: $stats['points'],
            position: 0,
        );
    }

    /**
     * Sorts standings by points and tie-breakers, including mini-league head-to-head metrics.
     *
     * @param  array<TeamStanding>  $standings
     * @return array<TeamStanding>
     */
    public function sortStandings(array $standings): array
    {
        $groups = [];

        foreach ($standings as $standing) {
            // First pass: group teams that are level on points.
            $groups[$standing->points][] = $standing;
        }

        krsort($groups, SORT_NUMERIC);

        $sorted = [];

        foreach ($groups as $group) {
            if (count($group) === 1) {
                $sorted[] = $group[0];
                continue;
            }

            $groupMetrics = $this->calculateGroupHeadToHeadMetrics($group);
            $hasHeadToHeadMatches = collect($groupMetrics)->sum('matches') > 0;

            usort($group, function (TeamStanding $a, TeamStanding $b) use ($groupMetrics, $hasHeadToHeadMatches): int {
                // Comparison tuple order:
                // 1) points, 2) overall GD, 3) goals scored, 4) head-to-head points, 5) head-to-head GD.
                $tupleA = [
                    $a->points,
                    $a->goalDifference,
                    $a->goalsFor,
                    $hasHeadToHeadMatches ? $groupMetrics[$a->team->id]['points'] : 0,
                    $hasHeadToHeadMatches ? $groupMetrics[$a->team->id]['gd'] : 0,
                ];

                $tupleB = [
                    $b->points,
                    $b->goalDifference,
                    $b->goalsFor,
                    $hasHeadToHeadMatches ? $groupMetrics[$b->team->id]['points'] : 0,
                    $hasHeadToHeadMatches ? $groupMetrics[$b->team->id]['gd'] : 0,
                ];

                foreach ([0, 1, 2, 3, 4] as $index) {
                    if ($tupleA[$index] !== $tupleB[$index]) {
                        return $tupleB[$index] <=> $tupleA[$index];
                    }
                }

                // Deterministic final fallback to keep ordering stable.
                return strcmp($a->team->name, $b->team->name);
            });

            foreach ($group as $standing) {
                $sorted[] = $standing;
            }
        }

        return array_values(array_map(
            fn (TeamStanding $standing, int $index): TeamStanding => $standing->withPosition($index + 1),
            $sorted,
            array_keys($sorted),
        ));
    }

    /**
     * @param  array<string, int>  $stats
     */
    private function applyResultToStats(array &$stats, bool $isHomeTeam, int $homeScore, int $awayScore): void
    {
        $teamGoals = $isHomeTeam ? $homeScore : $awayScore;
        $opponentGoals = $isHomeTeam ? $awayScore : $homeScore;

        $stats['played']++;
        $stats['goals_for'] += $teamGoals;
        $stats['goals_against'] += $opponentGoals;

        if ($teamGoals > $opponentGoals) {
            $stats['won']++;
            $stats['points'] += 3;

            return;
        }

        if ($teamGoals === $opponentGoals) {
            $stats['drawn']++;
            $stats['points'] += 1;

            return;
        }

        $stats['lost']++;
    }

    /**
     * @param  array<TeamStanding>  $group
     * @return array<int, array{points:int, gd:int, matches:int}>
     */
    private function calculateGroupHeadToHeadMetrics(array $group): array
    {
        $teamIds = array_map(fn (TeamStanding $standing): int => $standing->team->id, $group);
        $idSet = array_flip($teamIds);
        $metrics = [];

        foreach ($teamIds as $teamId) {
            $metrics[$teamId] = [
                'points' => 0,
                'gd' => 0,
                'matches' => 0,
            ];
        }

        foreach ($this->sortingResults as $result) {
            $homeTeamId = $result['home_team_id'];
            $awayTeamId = $result['away_team_id'];

            // Only matches where both teams are in the tied group count for mini-league metrics.
            if (! isset($idSet[$homeTeamId], $idSet[$awayTeamId])) {
                continue;
            }

            $homeScore = $result['home_score'];
            $awayScore = $result['away_score'];

            $metrics[$homeTeamId]['matches']++;
            $metrics[$awayTeamId]['matches']++;

            // Track goal difference symmetrically for both sides.
            $metrics[$homeTeamId]['gd'] += ($homeScore - $awayScore);
            $metrics[$awayTeamId]['gd'] += ($awayScore - $homeScore);

            if ($homeScore > $awayScore) {
                $metrics[$homeTeamId]['points'] += 3;
            } elseif ($awayScore > $homeScore) {
                $metrics[$awayTeamId]['points'] += 3;
            } else {
                $metrics[$homeTeamId]['points'] += 1;
                $metrics[$awayTeamId]['points'] += 1;
            }
        }

        return $metrics;
    }
}
