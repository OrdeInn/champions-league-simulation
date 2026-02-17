<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use App\Services\FixtureService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class FixtureController extends Controller
{
    public function __construct(private readonly FixtureService $fixtureService) {}

    public function generate(): RedirectResponse
    {
        $this->fixtureService->generate();

        return redirect()->route('fixtures.index');
    }

    public function index(): Response|RedirectResponse
    {
        if (! Fixture::query()->exists()) {
            return redirect()->route('teams.index');
        }

        $fixtures = Fixture::query()
            ->with('matches.homeTeam:id,name,short_name', 'matches.awayTeam:id,name,short_name')
            ->orderBy('week')
            ->get();

        return Inertia::render('Fixtures/Index', [
            'fixtures' => $fixtures,
        ]);
    }
}
