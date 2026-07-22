<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->seedTestPermissions();
    }

    protected function seedTestPermissions(): void
    {
        try {
            $permissions = [
                'view organizations', 'create organizations', 'edit organizations', 'delete organizations',
                'view users', 'create users', 'edit users', 'delete users',
                'view sports', 'create sports', 'edit sports', 'delete sports',
                'view sport categories', 'create sport categories', 'edit sport categories', 'delete sport categories',
                'view sessions', 'create sessions', 'edit sessions', 'delete sessions',
                'view tournaments', 'create tournaments', 'edit tournaments', 'delete tournaments',
                'view events', 'create events', 'edit events', 'delete events',
                'view participants', 'create participants', 'edit participants', 'delete participants',
                'view registrations', 'create registrations', 'edit registrations', 'delete registrations',
                'manage_matches', 'manage_results',
            ];

            foreach ($permissions as $p) {
                Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
            }

            Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
            Role::firstOrCreate(['name' => 'org-admin', 'guard_name' => 'web']);
            Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);
            Role::firstOrCreate(['name' => 'sport-coordinator', 'guard_name' => 'web']);
            Role::firstOrCreate(['name' => 'tournament-manager', 'guard_name' => 'web']);
        } catch (\Throwable $e) {
            // Permission table might not exist yet
        }
    }
}
