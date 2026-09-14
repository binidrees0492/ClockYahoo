<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader to load dependencies...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and instantiate the service container...
$app = require_once __DIR__.'/../bootstrap/app.php';

// Capture the incoming HTTP request and send it through the application kernel.
// Errors bubbling up to here mean a failure occurred inside your routes, controllers, or views.
$app->handleRequest(Request::capture());
