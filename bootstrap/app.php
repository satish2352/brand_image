<?php


// Add this block right at the top, before anything else.
if (!defined('CURL_SSLVERSION_TLSv1_2')) {
    define('CURL_SSLVERSION_TLSv1_2', 6);
}

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;

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

        // ✅ Add this
        $middleware->validateCsrfTokens(except: [
            'payment/webhook/razorpay',
            'payment/webhook/razorpay/*',
        ]);
        // Middleware aliases (unchanged)
        $middleware->alias([
            'SuperAdmin'   => \App\Http\Middleware\SuperAdmin::class,
            'website.auth' => \App\Http\Middleware\WebsiteAuth::class,
            'auth.both'    => \App\Http\Middleware\AuthBoth::class,
            'check.website.user' => \App\Http\Middleware\CheckWebsiteUserStatus::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {

        // PHP rejects an oversized upload before any controller runs, so the
        // user would otherwise see a raw 413 page. Send them back to the form
        // with the server's actual limit spelled out.
        $exceptions->render(function (PostTooLargeException $e, $request) {
            $limit = ini_get('post_max_size');

            $message = 'The upload is larger than this server accepts ('
                . $limit . ' in total for the Excel file and the images ZIP together). '
                . 'Please split the images into smaller ZIP batches and import them one at a time.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 413);
            }

            return redirect()
                ->back()
                ->withErrors(['images_zip' => $message]);
        });
    })
    ->create();
