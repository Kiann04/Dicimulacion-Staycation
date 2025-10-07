<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Show profile edit form
     */
    public function edit()
    {
        return view('profile'); // your Blade file
    }

    /**
     * Update profile info and photo
     */
    public function update(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'photo' => ['nullable', 'mimes:jpg,jpeg,png,gif,webp', 'max:1024'],
        ]);

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $this->saveProfilePhoto($user, $request->file('photo'));
        }

        // Update name and email
        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();

        return redirect()->back()->with('success', 'Profile updated successfully!');
    }

    /**
     * Save profile photo directly in public folder
     *
     * @param User $user
     * @param \Illuminate\Http\UploadedFile $photo
     */
    private function saveProfilePhoto(User $user, $photo)
    {
        $destination = public_path('uploads/profile_photos');

        // Ensure folder exists
        if (!file_exists($destination)) {
            if (!mkdir($destination, 0755, true)) {
                abort(500, 'Failed to create folder for profile photos. Check permissions.');
            }
        }

        // Delete old photo if exists
        if ($user->photo && file_exists(public_path($user->photo))) {
            unlink(public_path($user->photo));
        }

        // Save new photo
        $filename = time() . '_' . $photo->getClientOriginalName();
        if (!$photo->move($destination, $filename)) {
            abort(500, 'Failed to save the uploaded photo. Check folder permissions.');
        }

        // Update DB
        $user->photo = 'uploads/profile_photos/' . $filename;
        $user->save();
    }
}
