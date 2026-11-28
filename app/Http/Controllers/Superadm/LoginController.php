<?php

namespace App\Http\Controllers\Superadm;

use Validator;
use Illuminate\Http\Request;
use App\Support\AdminSession;
use App\Support\Recaptcha;
use App\Support\SessionFixation;
use App\Http\Middleware\SharedLinkVisitor;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class LoginController extends Controller
{
    public function __construct() {}

    public function loginsuper()
    {
        return view('superadm.login');
    }
    public function validateSuperLogin(Request $req)
    {
        /* ---------------- BASIC VALIDATION ---------------- */
        $rules = [
            'superemail'    => 'required|string',
            'superpassword' => 'required',
        ];

        if (config('services.recaptcha.enabled')) {
            $rules['g-recaptcha-response'] = 'required';
        }

        $req->validate($rules, [
            'superemail.required' => 'Enter user name',
            'superpassword.required' => 'Enter password',
            'g-recaptcha-response.required' => 'Please verify that you are not a robot',
        ]);

        /* ---------------- CAPTCHA CHECK (SAFE) ---------------- */
        if ($captchaError = $this->captchaError($req)) {
            return back()
                ->withErrors(['g-recaptcha-response' => $captchaError])
                ->withInput();
        }

        /* ---------------- USER AUTH ---------------- */
        $user = User::where('email', $req->superemail)
            ->where('is_deleted', 0)
            ->first();

        if (!$user) {
            return back()->with('error', 'Admin user not found. Contact the administrator for assistance.');
        }

        if ($user->is_active == 0) {
            return back()->with('error', 'This user account is deactivated. Contact the administrator for assistance.');
        }

        if (!Hash::check($req->superpassword, $user->password)) {
            return back()->with('error', 'User credentials not matching. Contact the administrator for assistance.');
        }

        /* ---------------- SESSION ---------------- */
        // A session id someone else planted must not come out of this holding
        // admin rights. Data survives it, so a customer signed in on the site
        // in this browser — and the registration form they may have open —
        // is left exactly as it was. See SessionFixation.
        SessionFixation::protect($req);

        // Opens the panel and admin mode on the site together — as before,
        // when both were the one `user_id` key. They part company at logout.
        AdminSession::login($req, $user);

        return redirect()->route('dashboard');
    }
    public function logOut(Request $req)
    {
        // Only the panel's own keys, not session()->flush(). Everything else
        // in this browser — a customer signed in on the public site, and admin
        // mode on that site, which has its own "Exit admin mode" — is none of
        // this logout's business.
        AdminSession::forgetPanel($req);

        // New session id, same data: the fixation protection flush() gave, for
        // whoever is still signed in here.
        SessionFixation::protect($req);

        return redirect()->route('login'); // redirect to super admin login page
    }

    /* ===================== TEAM LOGIN FROM THE PUBLIC SITE ===================== */

    /**
     * The same admin credentials, entered from the website's Account Access
     * modal instead of /login.
     *
     * Why this exists: generating a shareable shortlist happens on /search,
     * on the public site, and every part of it — the Select ticks, the Share
     * bar, the endpoint behind it, and the exemption from the preview/search
     * timer — keys off session('user_id'). Before this, a team member had to
     * go to /login, land on the dashboard and navigate back. Now they log in
     * where they already are.
     *
     * It grants exactly what /login grants, because it sets the same session
     * key that SuperAdmin middleware reads — this is the admin panel's front
     * door on a public page, so it is held to the admin login's standards and
     * then some: the captcha is verified server-side (which the customer login
     * beside it does NOT do), the route is rate limited, and failures are
     * deliberately vague so the form cannot be used to discover which
     * addresses are staff.
     */
    public function websiteAdminLogin(Request $req)
    {
        $req->validate([
            'admin_email'    => 'required|email',
            'admin_password' => 'required|string',
        ], [
            'admin_email.required'    => 'Please enter your admin email.',
            'admin_email.email'       => 'Enter a valid email address.',
            'admin_password.required' => 'Please enter your password.',
        ]);

        if ($captchaError = $this->captchaError($req)) {
            return response()->json(['status' => false, 'message' => $captchaError]);
        }

        $user = User::where('email', $req->admin_email)
            ->where('is_deleted', 0)
            ->first();

        // One message for "no such account", "wrong password" and "deactivated"
        // alike. /login names each case, which is fine behind a URL nobody
        // browses to; on the public site it would turn this box into a way of
        // asking whether a given address belongs to the team.
        $failed = response()->json([
            'status'  => false,
            'message' => 'These credentials do not match an active admin account.',
        ]);

        if (!$user || $user->is_active == 0 || !Hash::check($req->admin_password, $user->password)) {
            return $failed;
        }

        // Rotate the id before writing: one supplied by someone else must not
        // come out of this holding admin rights. Data survives it, so a cart,
        // a signed-in customer, or a half-filled registration form in this
        // browser is left exactly as it was. See SessionFixation.
        SessionFixation::protect($req);

        // Same as /login: admin mode here, and the panel behind the Admin
        // Panel link, so a team member can walk straight through to it.
        AdminSession::login($req, $user);

        return response()->json([
            'status'  => true,
            'message' => 'Signed in as ' . $user->name . '.',
        ]);
    }

    /**
     * Step out of admin mode without disturbing anything else in the browser.
     *
     * The public site's keys only: a customer account signed in beside it
     * keeps its cart, and the admin panel in the next tab stays open until it
     * is signed out of on its own.
     */
    public function websiteAdminLogout(Request $req)
    {
        AdminSession::forgetSite($req);

        // The shortlist a team member opened to check their own link. Left
        // behind, SharedLinkVisitor would now treat them as the client and
        // bounce them out of /search the moment they stop being an admin.
        $req->session()->forget(SharedLinkVisitor::SESSION_KEY);

        return redirect()->back()->with('success', 'Signed out of admin mode.');
    }

    /**
     * Verifies the reCAPTCHA response on the request.
     *
     * The check itself moved to App\Support\Recaptcha when the requirement
     * form needed the same thing — one implementation of "did the captcha
     * pass", so no entry point can quietly drift into skipping it. Kept as a
     * method here because both logins in this class read better calling it.
     */
    private function captchaError(Request $req): ?string
    {
        return Recaptcha::error($req);
    }
}
