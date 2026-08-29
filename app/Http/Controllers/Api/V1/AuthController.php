<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Token authentication for the Next.js client.
 *
 * The frontend is hosted on Vercel and the API on Hostinger, which are different
 * registrable domains. Sanctum's cookie/SPA mode depends on a shared parent
 * domain for its session and XSRF cookies, so it cannot be used here. This API
 * therefore issues Sanctum personal access tokens and expects them as
 *
 *     Authorization: Bearer <token>
 *
 * The token is returned once, at register or login, and is never readable again.
 * Clients should keep it out of localStorage where possible and send it only over
 * HTTPS. Tokens are named per device so a single device can be revoked.
 */
class AuthController extends Controller
{
    private const MAX_LOGIN_ATTEMPTS = 5;

    private const LOCKOUT_SECONDS = 60;

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => Hash::make($request->string('password')->toString()),
            'usertype' => 'user',
        ]);

        $token = $user->createToken($request->string('device_name')->toString() ?: 'api');

        return ApiResponse::success([
            'user' => new UserResource($user),
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ], 201, message: 'Account created.');
    }

    /**
     * Credentials are rate limited per email+IP in addition to the route-level
     * throttle, so an attacker cannot spread guesses for one account across many
     * requests without tripping the lockout.
     *
     * @throws ValidationException
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_LOGIN_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            return ApiResponse::error(
                "Too many login attempts. Please try again in {$seconds} seconds.",
                429,
                'too_many_attempts',
                ['retry_after' => $seconds],
            );
        }

        $user = User::where('email', $request->string('email')->toString())->first();

        if ($user === null || ! Hash::check($request->string('password')->toString(), $user->password)) {
            RateLimiter::hit($throttleKey, self::LOCKOUT_SECONDS);

            // A single generic message for both unknown email and wrong password,
            // so the endpoint cannot be used to enumerate registered accounts.
            throw ValidationException::withMessages([
                'email' => ['These credentials do not match our records.'],
            ]);
        }

        RateLimiter::clear($throttleKey);

        $token = $user->createToken($request->deviceName());

        return ApiResponse::success([
            'user' => new UserResource($user),
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
        ], message: 'Signed in.');
    }

    /** Revokes only the token used for this request, leaving other devices signed in. */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return ApiResponse::success(['revoked' => true], message: 'Signed out.');
    }

    /** Revokes every token for the account. */
    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return ApiResponse::success(['revoked' => true], message: 'Signed out on all devices.');
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(new UserResource($request->user()));
    }

    private function throttleKey(LoginRequest $request): string
    {
        return 'login:'.strtolower($request->string('email')->toString()).'|'.$request->ip();
    }
}
