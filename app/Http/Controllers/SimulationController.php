<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateMatchResultRequest;
use App\Models\Fixture;
use App\Models\GameMatch;
use App\Services\ChampionshipPredictionService;
use App\Services\LeagueTableService;
use App\Services\MatchSimulationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SimulationController extends Controller
{
    public function __construct(
        private readonly MatchSimulationService $matchSimulationService,
        private readonly LeagueTableService $leagueTableService,
        private readonly ChampionshipPredictionService $championshipPredictionService,
    ) {}

    public function index(): Response|RedirectResponse
    {
        if (! Fixture::query()->exists()) {
            return redirect()->route('teams.index');
        }

        $fixtures = Fixture::query()
            ->with('matches.homeTeam:id,name,short_name', 'matches.awayTeam:id,name,short_name')
            ->orderBy('week')
            ->get();

        $standings = array_map(
            fn ($standing): array => $standing->toArray(),
            $this->leagueTableService->getTable()
        );

        $predictions = $this->championshipPredictionService->getPredictions();
        $predictionPayload = $predictions === null
            ? null
            : array_map(fn ($prediction): array => $prediction->toArray(), $predictions);

        $currentWeek = (int) (Fixture::query()->played()->max('week') ?? 0);

        return Inertia::render('Simulation/Index', [
            'standings' => $standings,
            'fixtures' => $fixtures,
            'currentWeek' => $currentWeek,
            'predictions' => $predictionPayload,
            'allWeeksPlayed' => Fixture::query()->unplayed()->doesntExist(),
        ]);
    }

    public function playWeek(): RedirectResponse
    {
        $nextFixture = Fixture::query()
            ->unplayed()
            ->orderBy('week')
            ->first();

        if ($nextFixture !== null) {
            $this->matchSimulationService->simulateWeek($nextFixture);
        }

        return redirect()->route('simulation.index');
    }

    public function playAll(): RedirectResponse
    {
        $this->matchSimulationService->simulateAllRemainingWeeks();

        return redirect()->route('simulation.index');
    }

    public function updateMatch(UpdateMatchResultRequest $request, GameMatch $gameMatch): RedirectResponse
    {
        DB::transaction(function () use ($request, $gameMatch): void {
            $fixture = Fixture::query()
                ->lockForUpdate()
                ->findOrFail($gameMatch->fixture_id);

            $lockedMatch = GameMatch::query()
                ->whereKey($gameMatch->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedMatch->update([
                'home_score' => $request->integer('home_score'),
                'away_score' => $request->integer('away_score'),
                'is_played' => true,
            ]);

            $hasUnplayedMatches = GameMatch::query()
                ->where('fixture_id', $fixture->id)
                ->where('is_played', false)
                ->exists();

            $fixture->update([
                'is_played' => ! $hasUnplayedMatches,
            ]);
        });

        return redirect()->route('simulation.index');
    }

    public function reset(): RedirectResponse
    {
        $this->matchSimulationService->resetAllResults();

        return redirect()->route('simulation.index');
    }
}
