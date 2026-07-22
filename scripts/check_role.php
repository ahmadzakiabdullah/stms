<?php
$app = require __DIR__.'/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();
$u = User::where('id', 1)->first();
if (!$u) {
    echo 'User id=1 not found' . PHP_EOL;
    // Try with email
    $u = User::where('email', 'like', '%admin%')->first();
    if (!$u) $u = User::first();
}
if (!$u) {
    echo 'No user found' . PHP_EOL;
    exit;
}
echo 'User: ' . $u->name . ' (id=' . $u->id . ', uuid=' . $u->uuid . ')' . PHP_EOL;
echo 'Roles: ' . implode(', ', $u->getRoleNames()->toArray()) . PHP_EOL;
echo 'Has super-admin: ' . ($u->hasRole('super-admin') ? 'yes' : 'no') . PHP_EOL;
echo 'Has org-admin: ' . ($u->hasRole('org-admin') ? 'yes' : 'no') . PHP_EOL;
echo 'Org ID: ' . $u->organization_id . PHP_EOL;

$ep = EventParticipant::with('event')->first();
if ($ep) {
    echo 'First EP id=' . $ep->id . PHP_EOL;
    echo '  event: ' . ($ep->event ? $ep->event->name . ' (org=' . $ep->event->organization_id . ')' : 'NULL') . PHP_EOL;
    echo '  user org: ' . $u->organization_id . PHP_EOL;
    echo '  same org: ' . ($ep->event && $ep->event->organization_id === $u->organization_id ? 'yes' : 'no') . PHP_EOL;
}
