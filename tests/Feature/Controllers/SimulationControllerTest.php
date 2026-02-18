<?php

namespace Tests\Feature\Controllers;

use App\Models\Fixture;
use App\Models\GameMatch;
use App\Models\Team;
use App\Services\FixtureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SimulationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_simulation_page(): void
    {
        Team::factory()->count(4)->create();
        (new FixtureService())->generate();

        $this->get(route('simulation.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('Simulation/Index', false)
                ->has('standings', 4)
                ->has('fixtures', 6)
                ->has('currentWeek')
                ->has('predictions')
                ->has('allWeeksPlayed')
                ->where('navigation.fixturesAvailable', true)
                ->where('navigation.simulationAvailable', true)
            );
    }

    public function test_index_redirects_if_no_fixtures(): void
    {
        Team::factory()->count(4)->create();

        $this->get(route('simulation.index'))
            ->assertRedirect(route('teams.index'));
    }

    public function test_play_week_simulates_one_week(): void
    {
        Team::factory()->count(4)->create();
        (new FixtureService())->generate();

        $this->post(route('simulation.play-week'))
            ->assertRedirect(route('simulation.index'));

        $this->assertSame(1, Fixture::query()->where('is_played', true)->count());
    }

    public function test_play_all_simulates_all_weeks(): void
    {
        Team::factory()->count(4)->create();
        (new FixtureService())->generate();

        $this->post(route('simulation.play-all'))
            ->assertRedirect(route('simulation.index'));

        $this->assertSame(6, Fixture::query()->where('is_played', true)->count());
    }

    public function test_update_match_changes_scores(): void
    {
        Team::factory()->count(4)->create();
        (new FixtureService())->generate();

        $match = GameMatch::query()->firstOrFail();

        $this->put(route('simulation.update-match', $match->id), [
            'home_score' => 3,
            'away_score' => 2,
        ])->assertRedirect(route('simulation.index'));

        $this->assertSame(3, $match->refresh()->home_score);
        $this->assertSame(2, $match->refresh()->away_score);
    }

    public function test_update_match_validates_input(): void
    {
        Team::factory()->count(4)->create();
        (new FixtureService())->generate();

        $match = GameMatch::query()->firstOrFail();

        $this->from(route('simulation.index'))
            ->put(route('simulation.update-match', $match->id), ['home_score' => -1, 'away_score' => 'bad'])
            ->assertRedirect(route('simulation.index'))
            ->assertSessionHasErrors(['home_score', 'away_score']);
    }

    public function test_reset_clears_all_results(): void
    {
        Team::factory()->count(4)->create();
        (new FixtureService())->generate();
        $this->post(route('simulation.play-all'));

        $this->post(route('simulation.reset'))
            ->assertRedirect(route('teams.index'));

        $this->assertSame(0, GameMatch::query()->count());
        $this->assertSame(0, Fixture::query()->count());
    }
}
