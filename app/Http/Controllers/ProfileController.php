<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile');
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required','email','max:255', Rule::unique('users')->ignore($user->id)],
            'photo' => ['nullable','mimes:jpg,jpeg,png','max:1024'],
        ]);

        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($user->photo && Storage::exists('public/'.$user->photo)) {
                Storage::delete('public/'.$user->photo);
            }

            // Store new photo with unique name
            $path = $request->file('photo')->store('profile_photos', 'public');
            $user->photo = $path;
        }

        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();

        return redirect()->back()->with('success', 'Profile updated successfully!');
    }
}
