<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Force HTTPS recognition (common for reverse proxy / load balancer / IIS setups like saf.utem.edu.my/portal)
// This ensures url(), asset(), Inertia requests, and redirects use https:// instead of http://
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['SERVER_PORT'] = 443;
    $_SERVER['REQUEST_SCHEME'] = 'https';
}

// Fallback: always force on for this deployment if you prefer the simple version you usually use
// $_SERVER['HTTPS'] = 'on';
// $_SERVER['SERVER_PORT'] = 443;
// $_SERVER['REQUEST_SCHEME'] = 'https';

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
