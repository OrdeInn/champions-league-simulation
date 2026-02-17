<?php

namespace Tests\Feature\Controllers;

use App\Models\Fixture;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FixtureControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_creates_fixtures_and_redirects(): void
    {
        Team::factory()->count(4)->create();

        $this->post(route('fixtures.generate'))
            ->assertRedirect(route('fixtures.index'));

        $this->assertSame(6, Fixture::query()->count());
    }

    public function test_index_shows_fixtures(): void
    {
        Team::factory()->count(4)->create();
        $this->post(route('fixtures.generate'));

        $this->get(route('fixtures.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page): Assert => $page
                ->component('Fixtures/Index', false)
                ->has('fixtures', 6)
            );
    }

    public function test_index_redirects_if_no_fixtures(): void
    {
        Team::factory()->count(4)->create();

        $this->get(route('fixtures.index'))
            ->assertRedirect(route('teams.index'));
    }

    public function test_regenerate_replaces_old_fixtures(): void
    {
        Team::factory()->count(4)->create();

        $this->post(route('fixtures.generate'));
        $firstIds = Fixture::query()->pluck('id')->all();

        $this->post(route('fixtures.generate'));

        $this->assertSame(6, Fixture::query()->count());
        $this->assertNotSame($firstIds, Fixture::query()->pluck('id')->all());
    }
}
