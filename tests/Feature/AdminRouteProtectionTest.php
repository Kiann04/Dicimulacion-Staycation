<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Inquiry;
use App\Models\Staycation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\BuildsStayDates;
use Tests\TestCase;

class AdminRouteProtectionTest extends TestCase
{
    use BuildsStayDates, RefreshDatabase;

    /**
     * Every administrator route, including the ones that used to sit outside
     * the protected group.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function adminRouteProvider(): array
    {
        return [
            'dashboard' => ['get', '/admin/dashboard'],
            'customers' => ['get', '/admin/customers'],
            'analytics' => ['get', '/admin/analytics'],
            'settings' => ['get', '/admin/settings'],
            'audit logs' => ['get', '/admin/audit-logs'],
            'messages' => ['get', '/admin/messages'],
            'cancelled bookings' => ['get', '/admin/cancelled'],
            'blocked dates' => ['get', '/admin/blocked-dates'],
            'add staff form' => ['get', '/admin/add-staff'],
            'create staff' => ['post', '/admin/create-staff'],
            'staff list' => ['get', '/admin/staff/list'],
            'delete staff' => ['delete', '/admin/staff/delete/1'],
            'edit staycation' => ['get', '/admin/staycation/1/edit'],
            'update staycation' => ['put', '/admin/staycation/1'],
            'generate report' => ['post', '/admin/reports/generate'],
            'download period report' => ['get', '/admin/reports/download/annual/2026'],
            'mark fully paid' => ['post', '/admin/bookings/1/mark-fully-paid'],
            'update payment' => ['post', '/admin/bookings/1/update-payment'],
            'delete booking' => ['delete', '/admin/bookings/1'],
            'booking proof' => ['get', '/admin/bookings/1/proof'],
            'unpaid count' => ['get', '/admin/unpaid-count'],
            'delete message' => ['delete', '/admin/messages/1'],
            'update booking' => ['put', '/admin/update_booking/1'],
            'block dates' => ['post', '/admin/blocked-dates'],
        ];
    }

    /**
     * @dataProvider adminRouteProvider
     */
    public function test_a_guest_is_redirected_away_from_admin_routes(string $method, string $uri): void
    {
        $this->{$method}($uri)->assertRedirect(route('login'));
    }

    /**
     * @dataProvider adminRouteProvider
     */
    public function test_a_customer_is_forbidden_from_admin_routes(string $method, string $uri): void
    {
        $this->actingAs(User::factory()->create())
            ->{$method}($uri)
            ->assertForbidden();
    }

    /**
     * @dataProvider adminRouteProvider
     */
    public function test_staff_are_forbidden_from_admin_routes(string $method, string $uri): void
    {
        $this->actingAs(User::factory()->staff()->create())
            ->{$method}($uri)
            ->assertForbidden();
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function staffRouteProvider(): array
    {
        return [
            'dashboard' => ['get', '/staff/dashboard'],
            'customers' => ['get', '/staff/customers'],
            'bookings' => ['get', '/staff/bookings'],
            'messages' => ['get', '/staff/messages'],
        ];
    }

    /**
     * @dataProvider staffRouteProvider
     */
    public function test_a_guest_is_redirected_away_from_staff_routes(string $method, string $uri): void
    {
        $this->{$method}($uri)->assertRedirect(route('login'));
    }

    /**
     * @dataProvider staffRouteProvider
     */
    public function test_a_customer_is_forbidden_from_staff_routes(string $method, string $uri): void
    {
        $this->actingAs(User::factory()->create())
            ->{$method}($uri)
            ->assertForbidden();
    }

    public function test_an_administrator_may_not_be_created_by_an_anonymous_request(): void
    {
        $this->post('/admin/create-staff', [
            'name' => 'Intruder',
            'email' => 'intruder@example.com',
            'password' => 'password123',
        ])->assertRedirect(route('login'));

        $this->assertDatabaseMissing('users', ['email' => 'intruder@example.com']);
    }

    public function test_a_customer_cannot_delete_another_users_account(): void
    {
        $victim = User::factory()->staff()->create();

        $this->actingAs(User::factory()->create())
            ->delete('/admin/staff/delete/'.$victim->getKey())
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $victim->getKey()]);
    }

    public function test_a_customer_cannot_move_a_bookings_dates(): void
    {
        $booking = Booking::factory()->create();

        $this->actingAs(User::factory()->create())
            ->put('/admin/update_booking/'.$booking->getKey(), [
                'staycation_id' => $booking->staycation_id,
                'name' => 'Hijacked',
                'phone' => '09123456789',
                'guest_number' => 2,
                'start_date' => $this->day(92),
                'end_date' => $this->day(96),
            ])
            ->assertForbidden();

        $this->assertNotSame('Hijacked', $booking->refresh()->name);
    }

    public function test_deleting_a_message_is_not_reachable_by_a_get_request(): void
    {
        $inquiry = Inquiry::query()->create([
            'email' => 'guest@example.com',
            'message' => 'Please delete me',
            'status' => 'unread',
        ]);

        // A destructive action behind GET can be triggered by a prefetch, a
        // crawler or an <img> tag, and carries no CSRF token.
        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/messages/delete/'.$inquiry->getKey())
            ->assertNotFound();

        $this->assertDatabaseHas('inquiries', ['id' => $inquiry->getKey()]);
    }

    public function test_an_administrator_deletes_a_message_with_a_delete_request(): void
    {
        $inquiry = Inquiry::query()->create([
            'email' => 'guest@example.com',
            'message' => 'Please delete me',
            'status' => 'unread',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.delete_message', $inquiry))
            ->assertRedirect(route('admin.messages'));

        $this->assertDatabaseMissing('inquiries', ['id' => $inquiry->getKey()]);
    }

    /**
     * CSRF protection itself comes from the `web` middleware group, and Laravel
     * deliberately bypasses VerifyCsrfToken while tests run, so it cannot be
     * asserted directly. What is assertable is that the route accepts only
     * DELETE and carries the web group — which is what makes the token apply.
     */
    public function test_message_deletion_accepts_only_the_delete_verb_under_the_web_group(): void
    {
        $route = Route::getRoutes()->getByName('admin.delete_message');

        $this->assertNotNull($route);
        $this->assertSame(['DELETE'], array_values(array_diff($route->methods(), ['HEAD'])));
        $this->assertContains('web', $route->gatherMiddleware());
    }

    // --------------------------------------------------- staycation pricing

    public function test_a_negative_nightly_rate_is_rejected_on_update(): void
    {
        $staycation = Staycation::factory()->create(['house_price' => 2000]);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.update_staycation', $staycation), [
                'house_name' => $staycation->house_name,
                'house_description' => 'A description',
                'house_price' => -100,
                'house_location' => 'Somewhere',
                'house_availability' => 'available',
            ])
            ->assertSessionHasErrors('house_price');

        $this->assertEquals(2000, $staycation->fresh()->house_price);
    }

    public function test_a_valid_nightly_rate_is_accepted_on_update(): void
    {
        $staycation = Staycation::factory()->create(['house_price' => 2000]);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.update_staycation', $staycation), [
                'house_name' => $staycation->house_name,
                'house_description' => 'A description',
                'house_price' => 2500,
                'house_location' => 'Somewhere',
                'house_availability' => 'available',
            ])
            ->assertSessionHasNoErrors();

        $this->assertEquals(2500, $staycation->fresh()->house_price);
    }

    public function test_a_negative_nightly_rate_is_rejected_on_create(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.staycations.store'), [
                'house_name' => 'Cheap House',
                'house_description' => 'A description',
                'house_price' => -1,
                'house_image' => UploadedFile::fake()->create('house.jpg', 64, 'image/jpeg'),
                'house_location' => 'Somewhere',
                'house_availability' => 'available',
            ])
            ->assertSessionHasErrors('house_price');

        $this->assertDatabaseMissing('staycations', ['house_name' => 'Cheap House']);
    }

    public function test_an_administrator_can_reach_the_staff_list(): void
    {
        User::factory()->staff()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get('/admin/staff/list')
            ->assertOk();
    }

    public function test_an_administrator_cannot_move_a_booking_onto_occupied_dates(): void
    {
        $staycation = Staycation::factory()->create();
        Booking::factory()->for($staycation)->forDates($this->day(101), $this->day(106))->create();
        $booking = Booking::factory()->for($staycation)->forDates($this->day(122), $this->day(126))->create();

        $this->actingAs(User::factory()->admin()->create())
            ->put('/admin/update_booking/'.$booking->getKey(), [
                'staycation_id' => $staycation->getKey(),
                'name' => $booking->name,
                'phone' => '09123456789',
                'guest_number' => 2,
                'start_date' => $this->day(103),
                'end_date' => $this->day(105),
            ])
            ->assertSessionHas('error');

        $this->assertSame($this->day(122), $booking->refresh()->start_date->toDateString());
    }
}
