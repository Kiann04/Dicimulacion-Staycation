<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            // Check user role / type
            if (Auth::user()->usertype === 'admin') {
                return redirect()->route('admin.dashboard');
            } else {
                // guest / user
                return redirect()->route('home');
            }
        }

        return back()->withErrors([
            'email' => 'Invalid login details.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
