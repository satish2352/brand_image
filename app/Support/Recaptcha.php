<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Server-side reCAPTCHA verification.
 *
 * One implementation of "did the captcha pass", shared by every form that
 * shows the widget. The widget itself proves nothing: a response token is
 * only worth what Google says about it, and a form that renders the box but
 * never posts the token back for checking is decoration. Keeping the check
 * here is what stops a new form from quietly shipping as decoration.
 *
 * Returns null when it passes — and when the feature is switched off, so a
 * caller can always treat null as "carry on".
 */
class Recaptcha
{
    /** The field the widget posts under; also the key errors are reported on. */
    public const FIELD = 'g-recaptcha-response';

    public static function enabled(): bool
    {
        return (bool) config('services.recaptcha.enabled');
    }

    public static function error(Request $request): ?string
    {
        if (!self::enabled()) {
            return null;
        }

        if (!$request->filled(self::FIELD)) {
            return 'Please verify that you are not a robot';
        }

        try {
            $result = Http::asForm()->post(
                'https://www.google.com/recaptcha/api/siteverify',
                [
                    'secret'   => config('services.recaptcha.secret'),
                    'response' => $request->input(self::FIELD),
                    'remoteip' => $request->ip(),
                ]
            )->json();
        } catch (ConnectionException $e) {
            // Google unreachable. Said plainly rather than letting a cURL
            // failure surface as a 500 on a form someone is trying to send.
            return 'Captcha service unavailable. Try again later.';
        }

        return ($result['success'] ?? false) ? null : 'Captcha verification failed';
    }
}
