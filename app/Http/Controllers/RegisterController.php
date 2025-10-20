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
        // ✅ Validation (no numbers allowed)
        $request->validate([
            'first_name' => ['required', 'regex:/^[A-Za-z\s]+$/'],
            'middle_initial' => ['nullable', 'regex:/^[A-Za-z]?$/'],
            'last_name' => ['required', 'regex:/^[A-Za-z\s]+$/'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // ✅ Auto-format and clean input
        $firstName = ucwords(strtolower(trim($request->first_name)));
        $lastName = ucwords(strtolower(trim($request->last_name)));
        $middleInitial = !empty($request->middle_initial)
            ? strtoupper(trim($request->middle_initial)) . '.'
            : '';

        // ✅ Combine clean full name
        $fullName = trim("$firstName $middleInitial $lastName");
        $fullName = preg_replace('/\s+/', ' ', $fullName);

        // ✅ Clean email (remove extra spaces)
        $email = strtolower(trim($request->email));

        // ✅ Create user
        User::create([
            'name' => $fullName,
            'email' => $email,
            'password' => Hash::make($request->password),
            'usertype' => 'user',
        ]);

        // ✅ Redirect with success popup
        return redirect()->route('login')->with('success', 'Account created successfully! Please log in.');
    }
}
