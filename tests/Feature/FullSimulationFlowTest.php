<?php

namespace Tests\Feature;

use App\Models\Fixture;
use App\Models\GameMatch;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FullSimulationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_simulation_flow(): void
    {
        Team::factory()->count(4)->create();

        $this->post(route('fixtures.generate'))
            ->assertRedirect(route('fixtures.index'));

        $this->assertSame(6, Fixture::query()->count());
        $this->assertSame(12, GameMatch::query()->count());

        $this->post(route('simulation.play-all'))
            ->assertRedirect(route('simulation.index'));

        $this->assertSame(6, Fixture::query()->where('is_played', true)->count());

        $this->get(route('simulation.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('Simulation/Index', false)
                ->has('standings', 4)
                ->where('allWeeksPlayed', true)
                ->has('predictions', 4)
            );
    }
}
