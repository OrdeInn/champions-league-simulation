<?php

namespace App\Services;

use App\DataTransferObjects\TeamPrediction;
use App\Models\Fixture;
use App\Models\GameMatch;
use App\Models\Team;
use Illuminate\Support\Collection;

class ChampionshipPredictionService
{
    public function __construct(
        private readonly LeagueTableService $leagueTableService,
        private readonly MatchSimulationService $matchSimulationService,
        private readonly int $iterations = 1000,
    ) {}

    /**
     * @return array<TeamPrediction>|null
     */
    public function getPredictions(): ?array
    {
        if (! $this->shouldShowPredictions()) {
            return null;
        }

        $teams = Team::query()->orderBy('name')->get();

        if ($teams->isEmpty()) {
            return [];
        }

        $this->leagueTableService->setTeams($teams);

        if (Fixture::query()->unplayed()->count() === 0) {
            $table = $this->leagueTableService->getTable();
            $winnerTeamId = $table[0]->team->id;

            return $this->buildPredictionsFromProbabilities(
                $teams,
                array_map(
                    fn (Team $team): float => (int) $team->id === (int) $winnerTeamId ? 100.0 : 0.0,
                    $teams->all()
                )
            );
        }

        $winCounts = $this->runMonteCarloSimulation($this->iterations);

        $probabilities = [];

        foreach ($teams as $team) {
            $teamId = (int) $team->id;
            $probabilities[$teamId] = (($winCounts[$teamId] ?? 0) / max(1, $this->iterations)) * 100;
        }

        return $this->buildPredictionsFromProbabilities($teams, $probabilities);
    }

    public function shouldShowPredictions(): bool
    {
        return (int) Fixture::query()->played()->max('week') >= 4;
    }

    /**
     * @return array<int, int>
     */
    public function runMonteCarloSimulation(int $iterations): array
    {
        $teams = Team::query()->orderBy('name')->get();
        $this->leagueTableService->setTeams($teams);

        $winCounts = [];
        foreach ($teams as $team) {
            $winCounts[(int) $team->id] = 0;
        }

        $remainingMatches = GameMatch::query()
            ->where('is_played', false)
            ->with('homeTeam', 'awayTeam')
            ->get();

        if ($remainingMatches->isEmpty()) {
            $winnerTeamId = $this->leagueTableService->getTable()[0]->team->id;
            $winCounts[(int) $winnerTeamId] = $iterations;

            return $winCounts;
        }

        $realResults = GameMatch::query()
            ->where('is_played', true)
            ->get(['home_team_id', 'away_team_id', 'home_score', 'away_score'])
            ->map(fn (GameMatch $match): array => [
                'home_team_id' => (int) $match->home_team_id,
                'away_team_id' => (int) $match->away_team_id,
                'home_score' => (int) $match->home_score,
                'away_score' => (int) $match->away_score,
            ])
            ->all();

        for ($i = 0; $i < $iterations; $i++) {
            $simulatedResults = $this->simulateRemainingMatches($remainingMatches);
            $winnerTeamId = $this->calculateFinalStandings($realResults, $simulatedResults);
            $winCounts[$winnerTeamId] = ($winCounts[$winnerTeamId] ?? 0) + 1;
        }

        return $winCounts;
    }

    /**
     * @param  Collection<int, GameMatch>  $remainingMatches
     * @return array<int, array{match_id:int, home_team_id:int, away_team_id:int, home_score:int, away_score:int}>
     */
    public function simulateRemainingMatches(Collection $remainingMatches): array
    {
        $results = [];

        foreach ($remainingMatches as $match) {
            $homeExpectedGoals = $this->matchSimulationService->calculateExpectedGoals(
                $match->homeTeam,
                true,
                $match->awayTeam
            );
            $awayExpectedGoals = $this->matchSimulationService->calculateExpectedGoals(
                $match->awayTeam,
                false,
                $match->homeTeam
            );

            $results[(int) $match->id] = [
                'match_id' => (int) $match->id,
                'home_team_id' => (int) $match->home_team_id,
                'away_team_id' => (int) $match->away_team_id,
                'home_score' => $this->matchSimulationService->generateGoals($homeExpectedGoals),
                'away_score' => $this->matchSimulationService->generateGoals($awayExpectedGoals),
            ];
        }

        return $results;
    }

    /**
     * @param  array<int, array{home_team_id:int, away_team_id:int, home_score:int, away_score:int}>  $realResults
     * @param  array<int, array{match_id:int, home_team_id:int, away_team_id:int, home_score:int, away_score:int}>  $simulatedResults
     */
    public function calculateFinalStandings(array $realResults, array $simulatedResults): int
    {
        $allResults = $realResults;

        foreach ($simulatedResults as $simulatedResult) {
            $allResults[] = [
                'home_team_id' => (int) $simulatedResult['home_team_id'],
                'away_team_id' => (int) $simulatedResult['away_team_id'],
                'home_score' => (int) $simulatedResult['home_score'],
                'away_score' => (int) $simulatedResult['away_score'],
            ];
        }

        $table = $this->leagueTableService->getTableFromResults($allResults);

        return (int) $table[0]->team->id;
    }

    /**
     * @param  Collection<int, Team>  $teams
     * @param  array<int, float>  $probabilitiesByTeamId
     * @return array<TeamPrediction>
     */
    private function buildPredictionsFromProbabilities(Collection $teams, array $probabilitiesByTeamId): array
    {
        $rounded = [];
        $highestTeamId = null;
        $highestProbability = -1.0;

        foreach ($teams as $team) {
            $teamId = (int) $team->id;
            $probability = round((float) ($probabilitiesByTeamId[$teamId] ?? 0.0), 1);
            $rounded[$teamId] = $probability;

            if ($probability > $highestProbability) {
                $highestProbability = $probability;
                $highestTeamId = $teamId;
            }
        }

        $sum = array_sum($rounded);
        $difference = round(100.0 - $sum, 1);

        if ($highestTeamId !== null && abs($difference) > 0.0) {
            $rounded[$highestTeamId] = round(max(0.0, min(100.0, $rounded[$highestTeamId] + $difference)), 1);
        }

        $predictions = $teams
            ->map(function (Team $team) use ($rounded): TeamPrediction {
                $teamId = (int) $team->id;

                return new TeamPrediction(
                    team: $team,
                    probability: $rounded[$teamId] ?? 0.0,
                );
            })
            ->all();

        usort($predictions, function (TeamPrediction $a, TeamPrediction $b): int {
            if ($a->probability !== $b->probability) {
                return $b->probability <=> $a->probability;
            }

            return strcmp($a->team->name, $b->team->name);
        });

        return $predictions;
    }
}
