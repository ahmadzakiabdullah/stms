<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$controllerCode = file_get_contents(__DIR__.'/app/Http/Controllers/EventParticipantController.php');
if (str_contains($controllerCode, 'EventParticipant::find($id)')) {
    echo "Found EventParticipant::find(\$id) in loop\n";
} else {
    echo "Not found in loop\n";
}
