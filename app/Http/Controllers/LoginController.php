<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
            'email' => 'Invalid login details.'
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

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // ✅ Allow only Admin or Staff
            if (!in_array($user->usertype, ['admin', 'staff'])) {
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
        ])->onlyInput('email');
    }



    // ==========================
    // LOGOUT
    // ==========================
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home'); // go back to homepage after logout
    }
}
