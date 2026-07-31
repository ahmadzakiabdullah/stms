<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
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
}
