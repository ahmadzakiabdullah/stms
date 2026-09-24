<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$controllerCode = file_get_contents(__DIR__.'/app/Http/Controllers/EventParticipantController.php');
if (str_contains($controllerCode, 'EventParticipant::whereIn(')) {
    echo "Found EventParticipant::whereIn\n";
} else {
    echo "Not found EventParticipant::whereIn\n";
}
