<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * Indicates whether cookies should be serialized.
     *
     * @var bool
     */
    protected $addHttpCookie = true;

    /**
     * URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
          'payment/webhook/razorpay',
        // 'api/payment/webhook/razorpay',
    ];
}
