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
        $fullName = $request->first_name;

        if (!empty($request->middle_initial)) {
            $fullName .= ' ' . strtoupper($request->middle_initial) . '.';
        }

        $fullName .= ' ' . $request->last_name;

        // ✅ Remove double spaces just in case
        $fullName = preg_replace('/\s+/', ' ', trim($fullName));

        // ✅ Create user
        User::create([
            'name' => $fullName,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'usertype' => 'user',
        ]);

        // ✅ Redirect with success message
        return redirect()->route('login')->with('success', 'Account created successfully! Please log in.');
    }
}
