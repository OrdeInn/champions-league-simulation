<?php

namespace Tests\Feature;

use App\Models\Fixture;
use App\Models\GameMatch;
use App\Models\Team;
use App\Services\FixtureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ControllersAndRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_index_returns_inertia_page_with_teams_sorted_by_power(): void
    {
        $this->createTeams();

        $response = $this->get(route('teams.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->component('Teams/Index', false)
            ->has('teams', 4)
            ->where('teams.0.short_name', 'RMA')
            ->where('teams.1.short_name', 'LIV')
            ->where('teams.2.short_name', 'BAY')
            ->where('teams.3.short_name', 'GAL')
        );
    }

    public function test_fixture_generate_creates_fixtures_and_redirects(): void
    {
        $this->createTeams();

        $response = $this->post(route('fixtures.generate'));

        $response->assertRedirect(route('fixtures.index'));
        $this->assertSame(6, Fixture::query()->count());
        $this->assertSame(12, GameMatch::query()->count());
    }

    public function test_fixture_index_redirects_to_teams_when_no_fixtures_exist(): void
    {
        $this->createTeams();

        $response = $this->get(route('fixtures.index'));

        $response->assertRedirect(route('teams.index'));
    }

    public function test_simulation_index_returns_required_props(): void
    {
        $this->createTeams();
        (new FixtureService())->generate();
        $this->markWeeksAsPlayed(4);

        $response = $this->get(route('simulation.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page): Assert => $page
            ->component('Simulation/Index', false)
            ->has('standings', 4)
            ->has('fixtures', 6)
            ->where('currentWeek', 4)
            ->where('allWeeksPlayed', false)
            ->has('predictions', 4)
        );
    }

    public function test_simulation_index_redirects_to_teams_when_no_fixtures_exist(): void
    {
        $this->createTeams();

        $response = $this->get(route('simulation.index'));

        $response->assertRedirect(route('teams.index'));
    }

    public function test_play_week_advances_exactly_one_week(): void
    {
        $this->createTeams();
        (new FixtureService())->generate();

        $response = $this->post(route('simulation.play-week'));

        $response->assertRedirect(route('simulation.index'));
        $this->assertSame(1, Fixture::query()->where('is_played', true)->count());
        $this->assertTrue((bool) Fixture::query()->where('week', 1)->value('is_played'));
        $this->assertFalse((bool) Fixture::query()->where('week', 2)->value('is_played'));
    }

    public function test_play_week_is_noop_when_all_weeks_already_played(): void
    {
        $this->createTeams();
        (new FixtureService())->generate();
        $this->post(route('simulation.play-all'));

        $response = $this->post(route('simulation.play-week'));

        $response->assertRedirect(route('simulation.index'));
        $this->assertSame(6, Fixture::query()->where('is_played', true)->count());
        $this->assertSame(12, GameMatch::query()->where('is_played', true)->count());
    }

    public function test_play_all_completes_all_weeks(): void
    {
        $this->createTeams();
        (new FixtureService())->generate();

        $response = $this->post(route('simulation.play-all'));

        $response->assertRedirect(route('simulation.index'));
        $this->assertSame(6, Fixture::query()->where('is_played', true)->count());
        $this->assertSame(12, GameMatch::query()->where('is_played', true)->count());
    }

    public function test_update_match_validates_and_updates_scores_and_fixture_status(): void
    {
        $this->createTeams();
        (new FixtureService())->generate();

        $fixture = Fixture::query()->where('week', 1)->firstOrFail();
        $firstMatch = $fixture->matches()->orderBy('id')->firstOrFail();
        $secondMatch = $fixture->matches()->orderByDesc('id')->firstOrFail();

        $secondMatch->update([
            'home_score' => 1,
            'away_score' => 1,
            'is_played' => true,
        ]);

        $response = $this->put(route('simulation.update-match', $firstMatch->id), [
            'home_score' => 2,
            'away_score' => 0,
        ]);

        $response->assertRedirect(route('simulation.index'));

        $updatedMatch = $firstMatch->refresh();
        $this->assertTrue($updatedMatch->is_played);
        $this->assertSame(2, $updatedMatch->home_score);
        $this->assertSame(0, $updatedMatch->away_score);
        $this->assertTrue($fixture->refresh()->is_played);
    }

    public function test_update_match_rejects_invalid_scores(): void
    {
        $this->createTeams();
        (new FixtureService())->generate();
        $match = GameMatch::query()->firstOrFail();

        $negativeResponse = $this->from(route('simulation.index'))->put(route('simulation.update-match', $match->id), [
            'home_score' => -1,
            'away_score' => 0,
        ]);

        $negativeResponse->assertRedirect(route('simulation.index'));
        $negativeResponse->assertSessionHasErrors(['home_score']);

        $stringResponse = $this->from(route('simulation.index'))->put(route('simulation.update-match', $match->id), [
            'home_score' => 'two',
            'away_score' => 0,
        ]);

        $stringResponse->assertRedirect(route('simulation.index'));
        $stringResponse->assertSessionHasErrors(['home_score']);

        $awayInvalidResponse = $this->from(route('simulation.index'))->put(route('simulation.update-match', $match->id), [
            'home_score' => 1,
            'away_score' => 'bad',
        ]);

        $awayInvalidResponse->assertRedirect(route('simulation.index'));
        $awayInvalidResponse->assertSessionHasErrors(['away_score']);

        $maxBoundaryResponse = $this->from(route('simulation.index'))->put(route('simulation.update-match', $match->id), [
            'home_score' => 21,
            'away_score' => 0,
        ]);

        $maxBoundaryResponse->assertRedirect(route('simulation.index'));
        $maxBoundaryResponse->assertSessionHasErrors(['home_score']);
    }

    public function test_update_match_with_invalid_route_model_returns_not_found(): void
    {
        $this->createTeams();
        (new FixtureService())->generate();

        $response = $this->put(route('simulation.update-match', 999999), [
            'home_score' => 1,
            'away_score' => 0,
        ]);

        $response->assertNotFound();
    }

    public function test_reset_clears_all_match_data(): void
    {
        $this->createTeams();
        (new FixtureService())->generate();
        $this->post(route('simulation.play-all'));

        $response = $this->post(route('simulation.reset'));

        $response->assertRedirect(route('simulation.index'));
        $this->assertSame(0, Fixture::query()->where('is_played', true)->count());
        $this->assertSame(0, GameMatch::query()->where('is_played', true)->count());
        $this->assertSame(12, GameMatch::query()->whereNull('home_score')->whereNull('away_score')->count());
    }

    public function test_routes_are_named_and_resolve_correctly(): void
    {
        $this->createTeams();
        (new FixtureService())->generate();
        $match = GameMatch::query()->firstOrFail();

        $this->assertSame('/', route('teams.index', absolute: false));
        $this->assertSame('/fixtures/generate', route('fixtures.generate', absolute: false));
        $this->assertSame('/fixtures', route('fixtures.index', absolute: false));
        $this->assertSame('/simulation', route('simulation.index', absolute: false));
        $this->assertSame('/simulation/play-week', route('simulation.play-week', absolute: false));
        $this->assertSame('/simulation/play-all', route('simulation.play-all', absolute: false));
        $this->assertSame("/simulation/matches/{$match->id}", route('simulation.update-match', $match->id, absolute: false));
        $this->assertSame('/simulation/reset', route('simulation.reset', absolute: false));
    }

    /**
     * @return array<int, Team>
     */
    private function createTeams(): array
    {
        return [
            Team::create([
                'name' => 'Real Madrid',
                'short_name' => 'RMA',
                'power' => 90,
                'home_advantage' => 16,
                'goalkeeper_factor' => 17,
                'supporter_strength' => 18,
            ]),
            Team::create([
                'name' => 'Liverpool',
                'short_name' => 'LIV',
                'power' => 85,
                'home_advantage' => 15,
                'goalkeeper_factor' => 16,
                'supporter_strength' => 17,
            ]),
            Team::create([
                'name' => 'Bayern Munich',
                'short_name' => 'BAY',
                'power' => 82,
                'home_advantage' => 14,
                'goalkeeper_factor' => 15,
                'supporter_strength' => 15,
            ]),
            Team::create([
                'name' => 'Galatasaray',
                'short_name' => 'GAL',
                'power' => 72,
                'home_advantage' => 18,
                'goalkeeper_factor' => 12,
                'supporter_strength' => 19,
            ]),
        ];
    }

    private function markWeeksAsPlayed(int $maxWeek): void
    {
        $fixtures = Fixture::query()->with('matches')->orderBy('week')->get();

        foreach ($fixtures as $fixture) {
            if ($fixture->week > $maxWeek) {
                continue;
            }

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
