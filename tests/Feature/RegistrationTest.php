<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Laravel\Jetstream\Jetstream;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_registration_screen_cannot_be_rendered_if_support_is_disabled(): void
    {
        if (Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is enabled.');
        }

        $response = $this->get('/register');

        $response->assertStatus(404);
    }

    /**
     * Registration is handled by the application's own RegisterController,
     * which builds the full name from its parts and sends the new customer to
     * the login screen rather than signing them straight in.
     */
    public function test_new_users_can_register(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $response = $this->post('/register', [
            'first_name' => 'test',
            'middle_initial' => 'q',
            'last_name' => 'user',
            'email' => 'Test@Example.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
        ]);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseHas('users', [
            'name' => 'Test Q. User',
            'email' => 'test@example.com',
            'usertype' => 'user',
        ]);
    }

    public function test_a_weak_password_is_rejected(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'weak@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'weak@example.com']);
    }

    public function test_a_duplicate_email_is_rejected(): void
    {
        if (! Features::enabled(Features::registration())) {
            $this->markTestSkipped('Registration support is not enabled.');
        }

        User::factory()->create(['email' => 'taken@example.com']);

        $this->post('/register', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'taken@example.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
        ])->assertSessionHasErrors('email');

        $this->assertSame(1, User::query()->where('email', 'taken@example.com')->count());
    }
}
