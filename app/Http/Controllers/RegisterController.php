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
        // ✅ Validation rules (letters only for names + strong password)
        $request->validate([
            'first_name' => ['required', 'regex:/^[A-Za-z\s]+$/'],
            'middle_initial' => ['nullable', 'regex:/^[A-Za-z]?$/'],
            'last_name' => ['required', 'regex:/^[A-Za-z\s]+$/'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                // ✅ Must contain at least one uppercase letter and one number
                'regex:/^(?=.*[A-Z])(?=.*\d).+$/',
            ],
        ], [
            // ✅ Custom password error message
            'password.regex' => 'Password must contain at least one uppercase letter and one number.',
        ]);

        // ✅ Clean and format names
        $firstName = ucwords(strtolower(trim($request->first_name)));
        $lastName = ucwords(strtolower(trim($request->last_name)));
        $middleInitial = !empty($request->middle_initial)
            ? strtoupper(trim($request->middle_initial)) . '.'
            : '';

        // ✅ Combine clean full name (auto-handle missing MI)
        $fullName = trim("$firstName $middleInitial $lastName");
        $fullName = preg_replace('/\s+/', ' ', $fullName); // remove double spaces if MI is empty

        // ✅ Clean email
        $email = strtolower(trim($request->email));

        // ✅ Create user
        User::create([
            'name' => $fullName,
            'email' => $email,
            'password' => Hash::make($request->password),
            'usertype' => 'user',
        ]);

        // ✅ Redirect to login with popup success
        return redirect()->route('login')->with('success', 'Account created successfully! Please log in.');
    }
}
