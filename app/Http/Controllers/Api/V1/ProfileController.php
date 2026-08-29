<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\UpdatePasswordRequest;
use App\Http\Requests\Api\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success(new UserResource($request->user()));
    }

    /**
     * Changing the email address clears the verification timestamp, so a user
     * cannot inherit verified status on an address they have not proven they own.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $newEmail = $request->string('email')->toString();

        $user->name = $request->string('name')->toString();

        if ($newEmail !== $user->email) {
            $user->email = $newEmail;
            $user->email_verified_at = null;
        }

        $user->save();

        return ApiResponse::success(new UserResource($user->refresh()), message: 'Profile updated.');
    }

    /**
     * Requires the current password, and revokes every other token so a stolen
     * session cannot survive a password change.
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->forceFill([
            'password' => Hash::make($request->string('password')->toString()),
        ])->save();

        $currentTokenId = $request->user()->currentAccessToken()?->getKey();

        $user->tokens()
            ->when($currentTokenId !== null, fn ($query) => $query->whereKeyNot($currentTokenId))
            ->delete();

        return ApiResponse::success(
            ['password_updated' => true],
            message: 'Password updated. Other devices have been signed out.',
        );
    }
}
