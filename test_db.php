<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$eventsCreated = App\Models\Event::factory()->count(2)->create();
$ids = $eventsCreated->pluck('id')->toArray();

// Testing if standard Eloquent model events work fine on bulk delete
echo "Model uses SoftDeletes: " . in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive('App\Models\Event')) . "\n";
