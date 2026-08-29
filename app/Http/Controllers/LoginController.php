<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Session login for both the Blade application and the Next.js SPA.
 *
 * The SPA authenticates with Sanctum's cookie mode: it calls
 * GET /sanctum/csrf-cookie, then posts here with "Accept: application/json".
 * When the request expects JSON these actions answer with the API envelope
 * instead of a redirect, because a cross-origin fetch cannot follow a Blade
 * redirect or read errors flashed to the session.
 *
 * Blade behaviour is deliberately unchanged: form posts still redirect, still
 * flash errors, and /login still refuses anyone who is not a customer.
 */
class LoginController extends Controller
{
    // ==========================
    // USER LOGIN (/login)
    // ==========================
    public function showUserLoginForm()
    {
        return view('auth.user-login'); // Blade file for USER login
    }

    public function userLogin(Request $request)
    {
        $credentials = $request->only('email', 'password');

        /*
         * The Blade form is the customer portal, so it pins usertype to "user"
         * and an admin signing in there is rejected. The SPA has one login
         * screen for every role and decides where to send someone from the role
         * on /api/auth/me, so the JSON path authenticates any account and lets
         * Laravel's own middleware police what that account may then reach.
         */
        if ($request->expectsJson()) {
            return $this->jsonLogin($request, $credentials);
        }

        if (Auth::attempt(array_merge($credentials, ['usertype' => 'user']))) {
            $user = Auth::user();

            // ✅ If the user has 2FA enabled
            if ($user->two_factor_secret) {
                // Log out temporarily
                Auth::logout();

                // Store the user ID in session for later verification
                $request->session()->put('login.id', $user->id);

                // Redirect to Fortify's 2FA challenge page
                return redirect()->route('two-factor.login');
            }

            // If no 2FA, proceed to home
            $request->session()->regenerate();

            return redirect()->route('home');
        }

        return back()->withErrors([
            'email' => 'Invalid login details.',
        ])->onlyInput('email');
    }

    // ==========================
    // ADMIN + STAFF LOGIN (/admin/login)
    // ==========================
    public function showAdminStaffLoginForm()
    {
        return view('auth.admin-staff-login'); // Blade file for Admin + Staff login
    }

    public function adminStaffLogin(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if ($request->expectsJson()) {
            return $this->jsonLogin($request, $credentials, restrictToBackOffice: true);
        }

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // ✅ Allow only Admin or Staff
            if (! in_array($user->usertype, ['admin', 'staff'])) {
                Auth::logout();

                return back()->withErrors([
                    'email' => 'Only Admin or Staff can log in here.',
                ]);
            }

            // ✅ If user has 2FA enabled
            if ($user->two_factor_secret) {
                Auth::logout();

                // Store user ID in session for 2FA verification
                $request->session()->put('login.id', $user->id);

                // Redirect to Fortify's built-in 2FA challenge page
                return redirect()->route('two-factor.login');
            }

            // ✅ If no 2FA, go to correct dashboard
            $request->session()->regenerate();

            if ($user->usertype === 'admin') {
                return redirect()->route('admin.dashboard');
            } elseif ($user->usertype === 'staff') {
                return redirect()->route('staff.dashboard');
            }
        }

        // ❌ Invalid login
        return back()->withErrors([
            'email' => 'Invalid login details.',
        ]);
    }

    // ==========================
    // LOGOUT
    // ==========================
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            // 204 would be tidier, but the SPA reads the envelope to confirm the
            // server - not just the client - dropped the session.
            return ApiResponse::success(['authenticated' => false], message: 'Signed out.');
        }

        return redirect()->route('home'); // go back to homepage after logout
    }

    /**
     * Establishes a session for a JSON client and answers with the user.
     *
     * Failures are thrown as validation errors so they render through the same
     * 422 envelope as every other API failure, and the message is identical for
     * an unknown email and a wrong password so the endpoint cannot be used to
     * discover which accounts exist.
     *
     * @param  array<string, mixed>  $credentials
     *
     * @throws ValidationException
     */
    private function jsonLogin(Request $request, array $credentials, bool $restrictToBackOffice = false): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        $user = Auth::user();

        if ($restrictToBackOffice && ! in_array($user->usertype, ['admin', 'staff'], true)) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => ['Only Admin or Staff can log in here.'],
            ]);
        }

        /*
         * Two-factor accounts cannot complete a session here. The session is
         * dropped again and the client is told to run the challenge rather than
         * being handed a half-authenticated state.
         */
        if ($user->two_factor_secret) {
            Auth::logout();
            $request->session()->put('login.id', $user->id);

            return ApiResponse::success([
                'two_factor_required' => true,
            ], 200, message: 'Two-factor authentication is required.');
        }

        // Rotates the session id so a fixated pre-login id cannot be reused.
        $request->session()->regenerate();

        return ApiResponse::success([
            'user' => new UserResource($user),
        ], message: 'Signed in.');
    }
}
