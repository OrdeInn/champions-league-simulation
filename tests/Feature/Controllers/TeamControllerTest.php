<?php

namespace Tests\Feature\Controllers;

use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TeamControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_teams_page(): void
    {
        Team::factory()->count(4)->create();

        $this->get(route('teams.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('Teams/Index', false)
                ->has('teams', 4)
            );
    }

    public function test_teams_include_required_fields(): void
    {
        Team::factory()->count(4)->create();

        $this->get(route('teams.index'))
            ->assertInertia(fn (Assert $page): Assert => $page
                ->has('teams.0.id')
                ->has('teams.0.name')
                ->has('teams.0.short_name')
                ->has('teams.0.power')
            );
    }

    public function test_teams_ordered_by_power_descending(): void
    {
        Team::factory()->create(['power' => 60]);
        Team::factory()->create(['power' => 90]);
        Team::factory()->create(['power' => 80]);
        Team::factory()->create(['power' => 70]);

        $this->get(route('teams.index'))
            ->assertInertia(fn (Assert $page): Assert => $page
                ->where('teams.0.power', 90)
            );
    }
}
