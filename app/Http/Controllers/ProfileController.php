<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('profile'); // your Blade file
    }

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
        // Use DOCUMENT_ROOT to point directly to public_html (Hostinger)
        $destination = $_SERVER['DOCUMENT_ROOT'] . '/uploads/profile_photos';

        // Ensure folder exists
        if (!file_exists($destination)) {
            if (!mkdir($destination, 0755, true)) {
                abort(500, 'Failed to create folder for profile photos. Check permissions.');
            }
        }

        // Delete old photo if exists
        if ($user->photo && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $user->photo)) {
            unlink($_SERVER['DOCUMENT_ROOT'] . '/' . $user->photo);
        }

        // Sanitize filename (remove spaces & special chars)
        $originalName = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $photo->getClientOriginalExtension();
        $cleanName = preg_replace("/[^A-Za-z0-9_-]/", '_', $originalName);
        $filename = time() . '_' . $cleanName . '.' . $extension;

        // Move file to uploads folder
        if (!$photo->move($destination, $filename)) {
            abort(500, 'Failed to save the uploaded photo. Check folder permissions.');
        }

        // Save relative path in DB (so asset() works in Blade)
        $user->photo = 'uploads/profile_photos/' . $filename;
        $user->save();
    }
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = Auth::user();
        $user->password = Hash::make($request->password);
        $user->save();

        return back()->with('password_success', 'Password updated successfully.');
    }
  

}
