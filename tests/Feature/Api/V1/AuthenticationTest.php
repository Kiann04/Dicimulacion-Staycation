<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Token authentication for the cross-domain Next.js client.
 */
class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('login:member@example.com|127.0.0.1');
    }

    public function test_a_visitor_can_register_and_receives_a_token(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Ana Reyes',
            'email' => 'ana@example.com',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
            'device_name' => 'web',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user.email', 'ana@example.com')
            ->assertJsonPath('data.user.role', 'user')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonStructure(['data' => ['token']]);

        $this->assertDatabaseHas('users', ['email' => 'ana@example.com', 'usertype' => 'user']);
    }

    public function test_registration_never_returns_the_password_hash(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Ana Reyes',
            'email' => 'ana@example.com',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
        ]);

        $this->assertStringNotContainsString('password', strtolower(json_encode($response->json('data.user'))));
    }

    public function test_registration_rejects_a_duplicate_email(): void
    {
        User::factory()->create(['email' => 'ana@example.com']);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Ana Reyes',
            'email' => 'ana@example.com',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_a_registered_user_can_sign_in(): void
    {
        User::factory()->create([
            'email' => 'member@example.com',
            'password' => Hash::make('secret-password'),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'member@example.com',
            'password' => 'secret-password',
        ])
            ->assertOk()
            ->assertJsonPath('data.user.email', 'member@example.com')
            ->assertJsonStructure(['data' => ['token']]);
    }

    public function test_a_wrong_password_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'member@example.com',
            'password' => Hash::make('secret-password'),
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'member@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    /**
     * An unknown email and a wrong password must be indistinguishable, otherwise
     * the endpoint becomes an account-enumeration oracle.
     */
    public function test_an_unknown_email_gives_the_same_response_as_a_wrong_password(): void
    {
        User::factory()->create([
            'email' => 'member@example.com',
            'password' => Hash::make('secret-password'),
        ]);

        $wrongPassword = $this->postJson('/api/v1/auth/login', [
            'email' => 'member@example.com',
            'password' => 'wrong-password',
        ]);

        $unknownEmail = $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertSame($wrongPassword->status(), $unknownEmail->status());
        $this->assertSame($wrongPassword->json('errors'), $unknownEmail->json('errors'));
    }

    public function test_repeated_failed_logins_are_rate_limited(): void
    {
        User::factory()->create([
            'email' => 'member@example.com',
            'password' => Hash::make('secret-password'),
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'member@example.com',
                'password' => 'wrong-password',
            ])->assertStatus(422);
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => 'member@example.com',
            'password' => 'secret-password',
        ])
            ->assertStatus(429)
            ->assertJsonPath('error_code', 'too_many_attempts');
    }

    public function test_me_returns_the_authenticated_user(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->getKey())
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_me_requires_a_token(): void
    {
        $this->getJson('/api/v1/me')
            ->assertUnauthorized()
            ->assertJsonPath('error_code', 'unauthenticated');
    }

    public function test_logout_revokes_only_the_current_token(): void
    {
        $user = User::factory()->create();

        $keptToken = $user->createToken('phone');
        $currentToken = $user->createToken('laptop');

        $this->withHeader('Authorization', 'Bearer '.$currentToken->plainTextToken)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $currentToken->accessToken->getKey()]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $keptToken->accessToken->getKey()]);
    }

    public function test_logout_all_revokes_every_token(): void
    {
        $user = User::factory()->create();
        $user->createToken('phone');
        $token = $user->createToken('laptop');

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/v1/auth/logout-all')
            ->assertOk();

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_a_revoked_token_can_no_longer_be_used(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('laptop');

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        // The test process reuses one application instance, so the guard still
        // holds the user it resolved for the previous request. A real second HTTP
        // request would resolve from scratch; forgetting the guards reproduces that.
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
            ->getJson('/api/v1/me')
            ->assertUnauthorized();
    }

    public function test_changing_the_password_signs_other_devices_out(): void
    {
        $user = User::factory()->create(['password' => Hash::make('secret-password')]);

        $otherDevice = $user->createToken('phone');
        $currentToken = $user->createToken('laptop');

        $this->withHeader('Authorization', 'Bearer '.$currentToken->plainTextToken)
            ->putJson('/api/v1/profile/password', [
                'current_password' => 'secret-password',
                'password' => 'a-brand-new-password',
                'password_confirmation' => 'a-brand-new-password',
            ])
            ->assertOk();

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $otherDevice->accessToken->getKey()]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $currentToken->accessToken->getKey()]);
        $this->assertTrue(Hash::check('a-brand-new-password', $user->refresh()->password));
    }

    public function test_changing_the_password_requires_the_current_one(): void
    {
        $user = User::factory()->create(['password' => Hash::make('secret-password')]);

        Sanctum::actingAs($user);

        $this->putJson('/api/v1/profile/password', [
            'current_password' => 'not-the-password',
            'password' => 'a-brand-new-password',
            'password_confirmation' => 'a-brand-new-password',
        ])->assertStatus(422)->assertJsonValidationErrors(['current_password']);
    }

    public function test_updating_the_email_clears_the_verified_flag(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        Sanctum::actingAs($user);

        $this->putJson('/api/v1/profile', [
            'name' => $user->name,
            'email' => 'changed@example.com',
        ])
            ->assertOk()
            ->assertJsonPath('data.email_verified', false);
    }
}
