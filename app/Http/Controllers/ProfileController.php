<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Show profile page
     */
    public function edit()
    {
        return view('profile'); // your Blade file
    }

    /**
     * Update user profile
     */
    public function update(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        // Validate with named error bag 'profile'
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'photo' => ['nullable', 'mimes:jpg,jpeg,png,gif,webp', 'max:1024'],
        ], [
            'email.unique' => 'This email is already taken.',
            'photo.mimes' => 'Photo must be an image (jpg, jpeg, png, gif, webp).',
            'photo.max' => 'Photo must not exceed 1MB.',
        ], [], 'profile'); // <-- named error bag

        // Handle photo upload
        if ($request->hasFile('photo')) {
            $this->saveProfilePhoto($user, $request->file('photo'));
        }

        // Update name and email
        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();

        return redirect()->back()->with('profile_success', 'Profile updated successfully!');
    }

    /**
     * Update user password
     */
    public function updatePassword(Request $request)
    {
        // Validate with named error bag 'password'
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'min:8'],
        ], [
            'current_password.current_password' => 'Current password is incorrect.',
            'password.min' => 'New password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ], [], 'password'); // <-- named error bag "password"

        // Update password
        Auth::user()->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->back()->with('password_success', 'Password updated successfully!');
    }

    /**
     * Save profile photo in public folder
     */
    private function saveProfilePhoto(User $user, $photo)
    {
        $destination = $_SERVER['DOCUMENT_ROOT'] . '/uploads/profile_photos';

        if (!file_exists($destination)) {
            mkdir($destination, 0755, true);
        }

        if ($user->photo && file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $user->photo)) {
            unlink($_SERVER['DOCUMENT_ROOT'] . '/' . $user->photo);
        }

        $originalName = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $photo->getClientOriginalExtension();
        $cleanName = preg_replace("/[^A-Za-z0-9_-]/", '_', $originalName);
        $filename = time() . '_' . $cleanName . '.' . $extension;

        $photo->move($destination, $filename);

        $user->photo = 'uploads/profile_photos/' . $filename;
        $user->save();
    }
}
