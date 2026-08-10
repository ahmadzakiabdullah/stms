<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 * This file serves as the front controller for IIS when
 * the site root is not pointed directly to /public
 */

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$_SERVER['HTTPS'] = 'on';
$_SERVER['SERVER_PORT'] = 443;
$_SERVER['REQUEST_SCHEME'] = 'https';

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/bootstrap/app.php';

$request = Request::capture();
// IIS does not infer the application base URL when the physical directory is
// requested without a trailing slash. Canonicalize that one malformed request
// before Laravel route matching so `/saf/portal` becomes `/saf/portal/`.
if ($request->getBaseUrl() === '' && basename($request->path()) === basename(__DIR__)) {
    $queryString = $request->getQueryString();
    $location = '/'.trim($request->path(), '/').'/'.($queryString ? '?'.$queryString : '');

    header('Location: '.$location, true, 308);
    exit;
}

$app->handleRequest($request);
