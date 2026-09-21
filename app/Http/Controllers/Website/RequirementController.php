<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use App\Models\MediaRequirement;
use Illuminate\Http\Request;
use App\Support\Recaptcha;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * "Share your requirement" — the way out when a search window has closed.
 *
 * Every field is validated, but the strictness is graded by what the team
 * actually acts on. A name, a mobile number, an email and the campaign dates
 * are checked properly — those are how we call the client back and when we
 * schedule. The descriptive fields stay free text with a length and
 * "says something" floor, so nobody is bounced for writing "about 3 weeks"
 * in a duration field.
 */
class RequirementController extends Controller
{
    public function create(Request $request)
    {
        return view('website.requirement', [
            'user'   => Auth::guard('website')->user(),
            'source' => $request->query('from') === 'expired' ? 'search_expired' : 'direct',
        ]);
    }


    /**
     * The user's own submitted requirements.
     *
     * Where a returning user lands once their trial is spent: they cannot
     * search, but everything they have already sent us stays theirs to read.
     */
    public function mine()
    {
        $userId = Auth::guard('website')->id();

        return view('website.my-requirements', [
            'requirements' => MediaRequirement::where('user_id', $userId)
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    /**
     * Normalise before validating.
     *
     * The phone and budget fields are the ones people paste into rather than
     * type: "+91 98765 43210", "₹2,00,000". Stripping the decoration here means
     * the rules below can be strict without the form bouncing someone for
     * formatting we can perfectly well read.
     */
    protected function normalise(Request $request): void
    {
        $trimmed = [];

        foreach ($request->all() as $key => $value) {
            if (! is_string($value)) {
                $trimmed[$key] = $value;
                continue;
            }

            // The comments box is the one field where the client's own line
            // breaks are worth keeping; everywhere else runs of whitespace are
            // just stray keystrokes.
            $trimmed[$key] = $key === 'additional_comments'
                ? trim($value)
                : trim(preg_replace('/\s+/u', ' ', $value));
        }

        if (isset($trimmed['mobile_no'])) {
            // Drop spaces, dashes and a leading +91 / 0 before counting digits.
            $digits = preg_replace('/\D/', '', $trimmed['mobile_no']);
            $digits = preg_replace('/^(?:91|0)(?=\d{10}$)/', '', $digits);
            $trimmed['mobile_no'] = $digits;
        }

        // "₹2,00,000" and "8 hoardings" become numbers; "two lakh" is left
        // alone so the rules can say so, rather than being quietly emptied
        // into a field the client thinks they filled in.
        foreach (['approx_budget', 'required_media_count'] as $numeric) {
            if (isset($trimmed[$numeric]) && preg_match('/\d/', $trimmed[$numeric])) {
                $trimmed[$numeric] = preg_replace('/\D/', '', $trimmed[$numeric]);
            }
        }

        if (isset($trimmed['email'])) {
            $trimmed['email'] = strtolower($trimmed['email']);
        }

        $request->merge($trimmed);
    }

    public function store(Request $request)
    {
        // Before anything else: this form is public, unauthenticated, and drops
        // straight into a queue the team works through by hand, so it is worth
        // a bot's while. Checked server-side — the widget on the page proves
        // nothing on its own.
        if ($captchaError = Recaptcha::error($request)) {
            // Reported as a field error so it lands in the same summary and
            // the same @error slot as every other problem on the form, and so
            // everything the person typed comes back with it.
            throw ValidationException::withMessages([
                Recaptcha::FIELD => $captchaError,
            ]);
        }

        $this->normalise($request);

        $today = now()->startOfDay()->toDateString();

        $data = $request->validate([
            // People, not strings: a name we can greet and a number we can ring.
            'full_name'            => ['required', 'string', 'min:3', 'max:150', 'regex:/^[\p{L}][\p{L}\s.\'-]*$/u'],
            'mobile_no'            => ['required', 'digits:10', 'regex:/^[6-9]\d{9}$/'],
            'email'                => ['nullable', 'email:filter', 'max:150'],

            'campaign_name'        => ['nullable', 'string', 'min:2', 'max:150'],
            'city'                 => ['required', 'string', 'min:2', 'max:100', 'regex:/^[\p{L}][\p{L}\s.\'-]*$/u'],
            'area_location'        => ['required', 'string', 'min:3', 'max:255'],
            'media_type'           => ['required', 'string', 'min:3', 'max:150'],

            // Dates are the one place a wrong value costs the team real time:
            // they schedule against these, so no past starts and no end that
            // lands before its own start.
            'campaign_start_date'  => ['required', 'date_format:Y-m-d', 'after_or_equal:' . $today],
            'campaign_end_date'    => ['required', 'date_format:Y-m-d', 'after_or_equal:campaign_start_date'],

            // Free text on purpose — "about 3 weeks" is more useful to the team
            // than a rejected form — but it still has to say something.
            'campaign_duration'    => ['required', 'string', 'min:2', 'max:100'],
            'required_media_count' => ['required', 'integer', 'min:1', 'max:9999'],

            'approx_budget'        => ['nullable', 'integer', 'min:1000', 'max:999999999'],
            'target_audience'      => ['nullable', 'string', 'min:2', 'max:255'],
            'preferred_location'   => ['nullable', 'string', 'min:2', 'max:255'],
            'preferred_media_size' => ['nullable', 'string', 'min:2', 'max:100'],
            'additional_comments'  => ['nullable', 'string', 'max:2000'],

            'source'               => ['nullable', 'string', 'in:direct,search_expired'],
        ], [
            'full_name.regex'                  => 'Please enter a valid name — letters only.',
            'city.regex'                       => 'Please enter a valid city name — letters only.',
            'mobile_no.digits'                 => 'Enter a 10-digit mobile number.',
            'mobile_no.regex'                  => 'A mobile number must start with 6, 7, 8 or 9.',
            'email.email'                      => 'Enter a valid email address (e.g. name@company.com).',
            'campaign_start_date.after_or_equal' => 'The start date cannot be in the past.',
            'campaign_start_date.date_format'  => 'Please pick a valid start date.',
            'campaign_end_date.date_format'    => 'Please pick a valid end date.',
            'campaign_end_date.after_or_equal' => 'The end date cannot be before the start date.',
            'required_media_count.integer'     => 'Enter how many media units you need, as a number.',
            'approx_budget.integer'            => 'Enter the budget as a number, without symbols.',
            'approx_budget.min'                => 'Please enter a budget of at least ₹1,000.',
        ], [
            'full_name'            => 'full name',
            'mobile_no'            => 'mobile number',
            'area_location'        => 'area / location',
            'media_type'           => 'media type',
            'campaign_start_date'  => 'campaign start date',
            'campaign_end_date'    => 'campaign end date',
            'campaign_duration'    => 'campaign duration',
            'required_media_count' => 'required number of media',
            'approx_budget'        => 'approximate budget',
            'preferred_media_size' => 'preferred media size',
        ]);

        $data['user_id'] = Auth::guard('website')->id();
        $data['status']  = MediaRequirement::STATUS_NEW;
        $data['source']  = $data['source'] ?? 'direct';

        MediaRequirement::create($data);

        return redirect()
            ->route('website.requirement.create')
            ->with('success', 'Thank you. Your requirement has been sent to the Brand Adda team — we will get back to you shortly.');
    }
}
