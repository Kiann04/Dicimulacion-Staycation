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

    // Only allow login if usertype is user
    if (Auth::attempt(array_merge($credentials, ['usertype' => 'user']))) {
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
        $request->session()->regenerate();

        // Allow only admin or staff
        if (in_array(Auth::user()->usertype, ['admin', 'staff'])) {
            if (Auth::user()->usertype === 'admin') {
                return redirect()->route('admin.dashboard');
            } elseif (Auth::user()->usertype === 'staff') {
                return redirect()->route('staff.dashboard');
            }
        }

        // If not admin/staff → logout immediately
        Auth::logout();
        return back()->withErrors([
            'email' => 'Only Admin/Staff can login here.'
        ]);
    }

    return back()->withErrors([
        'email' => 'Invalid login details.'
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
