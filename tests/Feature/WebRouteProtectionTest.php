<?php

namespace Tests\Feature;

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\StaffMiddleware;
use App\Models\Booking;
use App\Models\Staycation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Guards the Blade application's access rules.
 *
 * Several admin routes previously sat outside the admin middleware group and
 * were reachable by anyone: creating and deleting staff accounts, editing
 * staycations, generating reports and marking bookings fully paid. These tests
 * fail if any of them is ever moved back out.
 */
class WebRouteProtectionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Routes carry middleware aliases ("admin"), not class names, so the alias
     * map is applied before checking.
     *
     * @return array<int, string>
     */
    private function routesMissingMiddleware(string $namePrefix, string $middlewareClass): array
    {
        $aliases = app('router')->getMiddleware();
        $missing = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName() ?? '';

            // The admin sign-in screen must stay reachable in order to sign in.
            if (! str_starts_with($name, $namePrefix) || str_contains($name, 'staff.login')) {
                continue;
            }

            $resolved = array_map(
                fn (string $middleware): string => $aliases[$middleware] ?? $middleware,
                $route->gatherMiddleware(),
            );

            if (! in_array($middlewareClass, $resolved, true)) {
                $missing[] = $route->methods()[0].' '.$route->uri().' ('.$name.')';
            }
        }

        return $missing;
    }

    /**
     * Every route whose name begins with "admin." must carry AdminMiddleware.
     * This catches a whole class of mistake rather than one URL at a time.
     */
    public function test_every_admin_named_route_is_behind_the_admin_middleware(): void
    {
        $unprotected = $this->routesMissingMiddleware('admin.', AdminMiddleware::class);

        $this->assertSame([], $unprotected, "Admin routes reachable without AdminMiddleware:\n".implode("\n", $unprotected));
    }

    public function test_every_staff_named_route_is_behind_the_staff_middleware(): void
    {
        $unprotected = $this->routesMissingMiddleware('staff.', StaffMiddleware::class);

        $this->assertSame([], $unprotected, "Staff routes reachable without StaffMiddleware:\n".implode("\n", $unprotected));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function previouslyUnprotectedAdminRouteProvider(): array
    {
        return [
            'staff list' => ['get', '/admin/staff/list'],
            'add staff form' => ['get', '/admin/add-staff'],
            'create staff' => ['post', '/admin/create-staff'],
            'blocked dates' => ['get', '/admin/blocked-dates'],
            'cancelled bookings' => ['get', '/admin/cancelled'],
            'reports' => ['get', '/admin/reports'],
        ];
    }

    /**
     * @dataProvider previouslyUnprotectedAdminRouteProvider
     */
    public function test_a_guest_is_turned_away_from_admin_routes(string $method, string $uri): void
    {
        $response = $this->{$method}($uri);

        $this->assertContains(
            $response->status(),
            [302, 401, 403],
            "{$uri} answered {$response->status()} to an anonymous visitor."
        );
    }

    /**
     * @dataProvider previouslyUnprotectedAdminRouteProvider
     */
    public function test_a_signed_in_customer_is_forbidden_from_admin_routes(string $method, string $uri): void
    {
        $this->actingAs(User::factory()->create());

        $this->{$method}($uri)->assertForbidden();
    }

    public function test_a_customer_cannot_create_a_staff_account(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post('/admin/create-staff', [
            'name' => 'Rogue Staff',
            'email' => 'rogue@example.com',
            'password' => 'password123',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'rogue@example.com']);
    }

    public function test_a_customer_cannot_delete_a_staff_account(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs(User::factory()->create());

        $this->delete("/admin/staff/delete/{$staff->getKey()}")->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $staff->getKey()]);
    }

    public function test_a_customer_cannot_mark_a_booking_fully_paid(): void
    {
        $booking = Booking::factory()->for(Staycation::factory())->halfPaid()->create();

        $this->actingAs(User::factory()->create());

        $this->post("/admin/bookings/{$booking->getKey()}/mark-fully-paid")->assertForbidden();
    }

    public function test_a_customer_cannot_edit_a_staycation(): void
    {
        $staycation = Staycation::factory()->create(['house_name' => 'Original Name']);

        $this->actingAs(User::factory()->create());

        $this->put("/admin/staycation/{$staycation->getKey()}", ['house_name' => 'Hijacked'])
            ->assertForbidden();

        $this->assertSame('Original Name', $staycation->refresh()->house_name);
    }

    // ---------------------------------------------------------------------
    // Customer booking routes
    // ---------------------------------------------------------------------

    public function test_booking_submission_requires_a_session(): void
    {
        $staycation = Staycation::factory()->create();

        $this->post("/booking/{$staycation->getKey()}/submit", [])
            ->assertRedirect('/login');

        $this->assertSame(0, Booking::count());
    }

    public function test_booking_preview_requires_a_session(): void
    {
        $staycation = Staycation::factory()->create();

        $this->post("/booking/{$staycation->getKey()}/preview", [])->assertRedirect('/login');
    }

    public function test_booking_history_requires_a_session(): void
    {
        $this->get('/booking/history')->assertRedirect('/login');
        $this->get('/booking-history')->assertRedirect('/login');
    }

    public function test_cancelling_a_booking_requires_a_session(): void
    {
        $booking = Booking::factory()->for(Staycation::factory())->pending()->create();

        $this->delete("/booking/{$booking->getKey()}/cancel")->assertRedirect('/login');

        $this->assertSame('pending', $booking->refresh()->status);
    }

    /**
     * /booking/history used to be declared after /booking/{id}, so the wildcard
     * swallowed it and the history page was unreachable at that URL.
     */
    public function test_the_booking_history_url_is_not_swallowed_by_the_booking_wildcard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/booking/history')->assertOk();
    }
}
