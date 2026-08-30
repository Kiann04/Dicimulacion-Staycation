<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $response->assertRedirect(route('home'));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    /**
     * The customer login screen is for customers only; administrators and
     * staff sign in through /admin/login.
     */
    public function test_staff_cannot_authenticate_through_the_customer_login_screen(): void
    {
        $staff = User::factory()->staff()->create();

        $this->post('/login', [
            'email' => $staff->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_administrators_are_sent_to_the_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_staff_are_sent_to_the_staff_dashboard(): void
    {
        $staff = User::factory()->staff()->create();

        $response = $this->post('/admin/login', [
            'email' => $staff->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($staff);
        $response->assertRedirect(route('staff.dashboard'));
    }

    public function test_a_customer_cannot_authenticate_through_the_admin_login_screen(): void
    {
        $customer = User::factory()->create();

        $this->post('/admin/login', [
            'email' => $customer->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
    }

    public function test_repeated_failed_logins_are_throttled(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 6) as $ignored) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertStatus(429);

        $this->assertGuest();
    }
}
