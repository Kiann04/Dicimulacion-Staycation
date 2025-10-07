<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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

        // Sanitize filename (remove spaces & special chars)
        $originalName = pathinfo($photo->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $photo->getClientOriginalExtension();
        $cleanName = preg_replace("/[^A-Za-z0-9_-]/", '_', $originalName);
        $filename = time() . '_' . $cleanName . '.' . $extension;

        // Move file and debug
        if (!$photo->move($destination, $filename)) {
            dd('Failed to move file! Check folder permissions and filename.');
        } else {
            dd('Saved at: ' . public_path('uploads/profile_photos/' . $filename));
        }

        // Save path in DB
        $user->photo = 'uploads/profile_photos/' . $filename;
        $user->save();
    }
}
