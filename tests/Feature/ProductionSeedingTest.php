<?php

namespace Tests\Feature;

use App\Models\Session;
use App\Models\Tournament;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DummyFootballMensResultsSeeder;
use Database\Seeders\DummyFutsalMenSeeder;
use Database\Seeders\SAF2026DataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ProductionSeedingTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_bootstrap_does_not_create_demo_users_or_operational_data(): void
    {
        config()->set('app.seed_demo_data', false);

        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseHas('organizations', ['slug' => 'utem']);
        $this->assertDatabaseHas('roles', ['name' => 'super-admin']);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('sports', 0);
        $this->assertDatabaseCount('event_sessions', 0);
    }

    public function test_saf_demo_seeder_requires_explicit_production_override(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config()->set('app.allow_demo_seeding', false);

        $this->expectException(RuntimeException::class);

        app(SAF2026DataSeeder::class)->run();
    }

    public function test_saf_demo_seeder_creates_one_tournament_for_the_official_window(): void
    {
        config()->set('app.seed_demo_data', true);

        $this->seed(DatabaseSeeder::class);

        $session = Session::where('slug', 'saf-2026')->firstOrFail();
        $this->assertSame('2026-10-13', $session->start_date->toDateString());
        $this->assertSame('2026-10-25', $session->end_date->toDateString());

        $tournaments = Tournament::where('session_id', $session->id)->get();
        $this->assertCount(1, $tournaments);
        $this->assertSame('Sukan Antara Fakulti Ke-20 2026', $tournaments->first()->name);
        $this->assertSame('2026-10-13', $tournaments->first()->start_date->toDateString());
        $this->assertSame('2026-10-25', $tournaments->first()->end_date->toDateString());
        $this->assertDatabaseMissing('tournaments', ['slug' => 'saf-2026-fasa-1']);
        $this->assertDatabaseMissing('tournaments', ['slug' => 'saf-2026-fasa-2']);
    }

    public function test_futsal_dummy_seeder_requires_explicit_production_override(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config()->set('app.allow_demo_seeding', false);

        $this->expectException(RuntimeException::class);

        app(DummyFutsalMenSeeder::class)->run();
    }

    public function test_football_results_dummy_seeder_requires_explicit_production_override(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        config()->set('app.allow_demo_seeding', false);

        $this->expectException(RuntimeException::class);

        app(DummyFootballMensResultsSeeder::class)->run();
    }
}
