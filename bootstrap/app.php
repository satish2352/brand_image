<?php


// Add this block right at the top, before anything else.
if (!defined('CURL_SSLVERSION_TLSv1_2')) {
    define('CURL_SSLVERSION_TLSv1_2', 6);
}

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware) {

        // GLOBAL middleware (runs on every request)
        $middleware->append(
            \App\Http\Middleware\ComingSoonMiddleware::class
        );

        // Middleware aliases (unchanged)
        $middleware->alias([
            'SuperAdmin'   => \App\Http\Middleware\SuperAdmin::class,
            'website.auth' => \App\Http\Middleware\WebsiteAuth::class,
            'auth.both'    => \App\Http\Middleware\AuthBoth::class,
            'check.website.user' => \App\Http\Middleware\CheckWebsiteUserStatus::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();

