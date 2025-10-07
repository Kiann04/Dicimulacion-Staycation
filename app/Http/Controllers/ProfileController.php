<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'photo' => ['nullable', 'mimes:jpg,jpeg,png,gif,webp', 'max:1024'],
        ]);

        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($user->photo && file_exists(public_path($user->photo))) {
                unlink(public_path($user->photo));
            }

            // Store new photo directly in public/uploads/profile_photos
            $destination = public_path('uploads/profile_photos');
            if (!file_exists($destination)) {
                mkdir($destination, 0755, true);
            }

            $filename = time() . '_' . $request->file('photo')->getClientOriginalName();
            $request->file('photo')->move($destination, $filename);

            $user->photo = 'uploads/profile_photos/' . $filename;
        }

        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();

        return redirect()->back()->with('success', 'Profile updated successfully!');
    }
}
