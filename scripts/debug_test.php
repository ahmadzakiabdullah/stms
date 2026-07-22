<?php
putenv('DB_DATABASE=db4safportal_test');
$_SERVER['DB_DATABASE'] = 'db4safportal_test';
$_ENV['DB_DATABASE'] = 'db4safportal_test';

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Organization;
use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Services\SessionService;

try {
    $org = Organization::factory()->create();
    echo 'Org created: ' . $org->id . PHP_EOL;
    
    $user = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    $user->assignRole($role);
    echo 'User created: ' . $user->getKey() . PHP_EOL;
    echo 'User Org ID: ' . ($user->organization_id ?? 'null') . PHP_EOL;
    echo 'Has super-admin: ' . ($user->hasRole('super-admin') ? 'yes' : 'no') . PHP_EOL;
    echo 'Auth check: ' . (auth()->check() ? 'yes' : 'no') . PHP_EOL;
    
    auth()->login($user);
    
    $data = [
        'name' => 'New Session',
        'slug' => 'new-session',
        'description' => 'Test session',
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-15',
        'is_active' => true,
    ];
    
    $service = new SessionService();
    $session = $service->createSession($data);
    echo 'Session created: ' . $session->id . PHP_EOL;
    
} catch (Throwable $e) {
    echo 'ERROR: ' . get_class($e) . ': ' . $e->getMessage() . PHP_EOL;
    echo $e->getTraceAsString() . PHP_EOL;
}
