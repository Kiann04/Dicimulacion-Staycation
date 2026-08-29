<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Next.js admin client authenticates with Sanctum's SPA cookie mode rather
 * than a bearer token: it primes the CSRF cookie, posts credentials to the
 * session login route, and then reads GET /api/auth/me on every page load.
 *
 * These tests pin that flow from the server's side, including the parts the
 * frontend cannot be trusted to enforce - that the role comes from the server,
 * and that a staff session is refused admin data even though the same session
 * is perfectly valid.
 */
class SpaSessionAuthTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['usertype' => 'admin', 'password' => bcrypt('password')]);
    }

    public function test_the_csrf_cookie_route_is_available_to_the_spa(): void
    {
        $this->get('/sanctum/csrf-cookie')->assertNoContent();
    }

    public function test_a_json_login_opens_a_session_and_returns_the_role(): void
    {
        $this->admin();

        $this->postJson('/login', ['email' => User::first()->email, 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.role', 'admin');

        $this->assertAuthenticated();
    }

    public function test_a_wrong_password_is_refused_with_the_validation_envelope(): void
    {
        $admin = $this->admin();

        $this->postJson('/login', ['email' => $admin->email, 'password' => 'wrong-password'])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors('email');

        $this->assertGuest();
    }

    public function test_an_unknown_email_is_indistinguishable_from_a_wrong_password(): void
    {
        $admin = $this->admin();

        $unknown = $this->postJson('/login', ['email' => 'nobody@example.com', 'password' => 'password']);
        $wrong = $this->postJson('/login', ['email' => $admin->email, 'password' => 'wrong-password']);

        $this->assertSame($unknown->json('errors.email'), $wrong->json('errors.email'));
    }

    public function test_the_json_login_never_returns_a_password_or_a_token(): void
    {
        $admin = $this->admin();

        $body = $this->postJson('/login', ['email' => $admin->email, 'password' => 'password'])->getContent();

        $this->assertStringNotContainsString('password', $body);
        $this->assertStringNotContainsString($admin->password, $body);
        $this->assertStringNotContainsString('token', $body);
    }

    public function test_me_is_unauthenticated_without_a_session(): void
    {
        $this->getJson('/api/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('error_code', 'unauthenticated');
    }

    public function test_me_returns_the_signed_in_user_with_their_role(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.role', 'admin')
            ->assertJsonPath('data.email', $admin->email);
    }

    public function test_a_customer_session_reports_the_customer_role(): void
    {
        $customer = User::factory()->create(['usertype' => 'user']);

        $this->actingAs($customer)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('data.role', 'user');
    }

    /**
     * The frontend role check is a UX affordance only. This is the assertion
     * that actually matters: a genuine, valid staff session is still refused
     * every admin mutation by Laravel, whatever the client chose to render.
     *
     * Staff may *read* back-office listings by design (BackOfficeMiddleware);
     * the authority boundary is the mutation, so that is what is asserted.
     */
    public function test_a_staff_session_is_refused_admin_mutations_by_the_server(): void
    {
        $staff = User::factory()->create(['usertype' => 'staff']);

        $this->actingAs($staff)
            ->postJson('/api/v1/admin/staycations', [
                'house_name' => 'Injected Villa',
                'house_description' => 'Should never be created.',
                'house_price' => 1000,
                'house_location' => 'Nowhere',
                'house_availability' => 'available',
            ])
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'forbidden');

        $this->assertDatabaseMissing('staycations', ['house_name' => 'Injected Villa']);
    }

    public function test_a_customer_session_is_refused_back_office_data_by_the_server(): void
    {
        $customer = User::factory()->create(['usertype' => 'user']);

        $this->actingAs($customer)
            ->getJson('/api/v1/admin/dashboard')
            ->assertStatus(403)
            ->assertJsonPath('error_code', 'forbidden');
    }

    public function test_an_admin_session_reaches_admin_data(): void
    {
        $this->actingAs($this->admin())
            ->getJson('/api/v1/admin/dashboard')
            ->assertOk();
    }

    public function test_logout_ends_the_session(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->postJson('/logout')
            ->assertOk()
            ->assertJsonPath('data.authenticated', false);

        $this->assertGuest();
    }

    public function test_the_admin_login_route_refuses_a_customer_over_json(): void
    {
        $customer = User::factory()->create(['usertype' => 'user', 'password' => bcrypt('password')]);

        $this->postJson('/admin/login', ['email' => $customer->email, 'password' => 'password'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $this->assertGuest();
    }

    public function test_the_admin_login_route_accepts_staff_over_json(): void
    {
        $staff = User::factory()->create(['usertype' => 'staff', 'password' => bcrypt('password')]);

        $this->postJson('/admin/login', ['email' => $staff->email, 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('data.user.role', 'staff');
    }

    /** The Blade customer portal must keep refusing back-office accounts. */
    public function test_the_blade_login_form_still_rejects_an_admin(): void
    {
        $admin = $this->admin();

        $this->from('/login')
            ->post('/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
