<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_correct_admin_login_authenticates_session_and_returns_user_data(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@dicimulacion.local',
            'password' => 'SecureAdminPass123!',
            'usertype' => 'admin',
        ]);

        $response = $this->postJson('/login', [
            'email' => 'admin@dicimulacion.local',
            'password' => 'SecureAdminPass123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'admin@dicimulacion.local')
            ->assertJsonPath('data.user.role', 'admin');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_bad_password_is_rejected(): void
    {
        User::factory()->create([
            'email' => 'admin@dicimulacion.local',
            'password' => 'CorrectPassword123!',
            'usertype' => 'admin',
        ]);

        $response = $this->postJson('/login', [
            'email' => 'admin@dicimulacion.local',
            'password' => 'WrongPassword!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->assertGuest();
    }

    public function test_api_auth_me_unauthenticated_returns_401(): void
    {
        $responseRoot = $this->getJson('/api/auth/me');
        $responseRoot->assertStatus(401);

        $responseV1 = $this->getJson('/api/v1/auth/me');
        $responseV1->assertStatus(401);
    }

    public function test_authenticated_admin_can_access_admin_endpoint(): void
    {
        $admin = User::factory()->create([
            'usertype' => 'admin',
        ]);

        $response = $this->actingAs($admin, 'web')->getJson('/api/v1/admin/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    public function test_authenticated_staff_receives_403_on_admin_endpoint(): void
    {
        $staff = User::factory()->create([
            'usertype' => 'staff',
        ]);

        // Attempting an admin-restricted action like creating a staycation
        $response = $this->actingAs($staff, 'web')->postJson('/api/v1/admin/staycations', [
            'name' => 'New Villa',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'forbidden');
    }

    public function test_logout_invalidates_authenticated_session(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@dicimulacion.local',
            'password' => 'AdminPass123!',
            'usertype' => 'admin',
        ]);

        // Log in
        $this->postJson('/login', [
            'email' => 'admin@dicimulacion.local',
            'password' => 'AdminPass123!',
        ])->assertStatus(200);

        $this->assertAuthenticated();

        // Log out
        $logoutResponse = $this->postJson('/logout');
        $logoutResponse->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertGuest();

        // Subsequent authenticated check should be 401
        $this->getJson('/api/auth/me')->assertStatus(401);
    }

    public function test_password_is_hashed_using_laravel_hashing(): void
    {
        $plainPassword = 'PlainAdminPassword987#';

        $user = User::factory()->create([
            'password' => $plainPassword,
            'usertype' => 'admin',
        ]);

        $this->assertNotEquals($plainPassword, $user->password);
        $this->assertTrue(Hash::check($plainPassword, $user->password));
        $this->assertNotEmpty(Hash::info($user->password)['algoName']);
    }
}
