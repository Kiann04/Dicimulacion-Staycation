<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class RegisterController extends Controller
{
    public function showRegisterForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        // ✅ Validation Rules (no numbers allowed)
        $request->validate([
            'first_name' => ['required', 'regex:/^[A-Za-z\s]+$/'],
            'middle_initial' => ['nullable', 'regex:/^[A-Za-z]?$/'],
            'last_name' => ['required', 'regex:/^[A-Za-z\s]+$/'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // ✅ Combine full name properly
        $fullName = trim($request->first_name . ' ' . strtoupper($request->middle_initial) . '. ' . $request->last_name);
        $fullName = preg_replace('/\s+/', ' ', $fullName); // remove double spaces if MI is empty

        // ✅ Create user
        User::create([
            'name' => $fullName,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'usertype' => 'user',
        ]);

        // ✅ Redirect after registration
        return redirect()->route('login')->with('success', 'Account created successfully! Please log in.');

    }
}
