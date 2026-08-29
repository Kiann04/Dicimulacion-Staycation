<?php

namespace Tests\Feature\Api\V1;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Staycation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Who may do what.
 *
 * Covers three separate concerns: unauthenticated access to protected routes,
 * one customer reaching another customer's data, and non-admins reaching
 * back-office operations.
 */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $otherCustomer;

    private User $staff;

    private User $admin;

    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Storage::fake('local');

        $this->owner = User::factory()->create();
        $this->otherCustomer = User::factory()->create();
        $this->staff = User::factory()->staff()->create();
        $this->admin = User::factory()->admin()->create();

        $this->booking = Booking::factory()
            ->for(Staycation::factory())
            ->for($this->owner)
            ->pending()
            ->create(['payment_proof' => 'payment_proofs/secret.jpg']);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function protectedRouteProvider(): array
    {
        return [
            'list own bookings' => ['getJson', '/api/v1/bookings'],
            'current user' => ['getJson', '/api/v1/me'],
            'profile' => ['getJson', '/api/v1/profile'],
            'admin dashboard' => ['getJson', '/api/v1/admin/dashboard'],
            'admin bookings' => ['getJson', '/api/v1/admin/bookings'],
            'admin customers' => ['getJson', '/api/v1/admin/customers'],
            'admin payments' => ['getJson', '/api/v1/admin/payments'],
            'admin staycations' => ['getJson', '/api/v1/admin/staycations'],
            'admin blocked dates' => ['getJson', '/api/v1/admin/blocked-dates'],
            'admin reviews' => ['getJson', '/api/v1/admin/reviews'],
        ];
    }

    /**
     * @dataProvider protectedRouteProvider
     */
    public function test_protected_routes_reject_anonymous_callers(string $method, string $uri): void
    {
        $this->{$method}($uri)
            ->assertUnauthorized()
            ->assertJsonPath('error_code', 'unauthenticated');
    }

    public function test_the_public_catalogue_stays_reachable_without_a_token(): void
    {
        Staycation::factory()->count(2)->create();

        $this->getJson('/api/v1/staycations')->assertOk();
    }

    // ---------------------------------------------------------------------
    // Ownership isolation
    // ---------------------------------------------------------------------

    public function test_a_customer_cannot_read_another_customers_booking(): void
    {
        Sanctum::actingAs($this->otherCustomer);

        $this->getJson("/api/v1/bookings/{$this->booking->getKey()}")
            ->assertForbidden()
            ->assertJsonPath('error_code', 'forbidden');
    }

    public function test_a_customer_cannot_cancel_another_customers_booking(): void
    {
        Sanctum::actingAs($this->otherCustomer);

        $this->deleteJson("/api/v1/bookings/{$this->booking->getKey()}")->assertForbidden();

        $this->assertSame(BookingStatus::Pending->value, $this->booking->refresh()->status);
    }

    public function test_a_customers_booking_list_contains_only_their_own(): void
    {
        Sanctum::actingAs($this->otherCustomer);

        $this->getJson('/api/v1/bookings')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_a_customer_cannot_download_another_customers_payment_proof(): void
    {
        Sanctum::actingAs($this->otherCustomer);

        $this->getJson("/api/v1/bookings/{$this->booking->getKey()}/proof")->assertForbidden();
    }

    public function test_the_owner_may_cancel_their_own_pending_booking(): void
    {
        Sanctum::actingAs($this->owner);

        $this->deleteJson("/api/v1/bookings/{$this->booking->getKey()}")->assertOk();

        $this->assertSame(BookingStatus::Cancelled->value, $this->booking->refresh()->status);
    }

    public function test_a_confirmed_booking_cannot_be_cancelled_by_the_customer(): void
    {
        $confirmed = Booking::factory()
            ->for(Staycation::factory())
            ->for($this->owner)
            ->confirmed()
            ->create();

        Sanctum::actingAs($this->owner);

        $this->deleteJson("/api/v1/bookings/{$confirmed->getKey()}")->assertForbidden();

        $this->assertSame(BookingStatus::Confirmed->value, $confirmed->refresh()->status);
    }

    // ---------------------------------------------------------------------
    // Back-office gating
    // ---------------------------------------------------------------------

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function adminOnlyRouteProvider(): array
    {
        return [
            'approve' => ['postJson', '/api/v1/admin/bookings/%d/approve'],
            'decline' => ['postJson', '/api/v1/admin/bookings/%d/decline'],
            'cancel' => ['postJson', '/api/v1/admin/bookings/%d/cancel'],
            'mark fully paid' => ['postJson', '/api/v1/admin/bookings/%d/mark-fully-paid'],
            'payment status' => ['putJson', '/api/v1/admin/bookings/%d/payment-status'],
        ];
    }

    /**
     * @dataProvider adminOnlyRouteProvider
     */
    public function test_a_customer_cannot_reach_admin_booking_operations(string $method, string $template): void
    {
        Sanctum::actingAs($this->owner);

        $this->{$method}(sprintf($template, $this->booking->getKey()))->assertForbidden();
    }

    /**
     * @dataProvider adminOnlyRouteProvider
     */
    public function test_staff_cannot_perform_admin_booking_operations(string $method, string $template): void
    {
        Sanctum::actingAs($this->staff);

        $this->{$method}(sprintf($template, $this->booking->getKey()))->assertForbidden();

        $this->assertSame(BookingStatus::Pending->value, $this->booking->refresh()->status);
    }

    public function test_staff_may_read_back_office_listings(): void
    {
        Sanctum::actingAs($this->staff);

        $this->getJson('/api/v1/admin/bookings')->assertOk();
        $this->getJson('/api/v1/admin/dashboard')->assertOk();
        $this->getJson('/api/v1/admin/customers')->assertOk();
    }

    public function test_a_customer_cannot_read_back_office_listings(): void
    {
        Sanctum::actingAs($this->owner);

        $this->getJson('/api/v1/admin/bookings')->assertForbidden();
        $this->getJson('/api/v1/admin/dashboard')->assertForbidden();
    }

    public function test_an_admin_can_approve_a_booking(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson("/api/v1/admin/bookings/{$this->booking->getKey()}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', BookingStatus::Approved->value);
    }

    public function test_an_admin_can_decline_a_booking_and_release_the_dates(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson("/api/v1/admin/bookings/{$this->booking->getKey()}/decline", [
            'reason' => 'Property unavailable.',
        ])->assertOk();

        $booking = $this->booking->refresh();

        $this->assertSame(BookingStatus::Declined->value, $booking->status);
        $this->assertFalse($booking->blocksAvailability());
    }

    public function test_a_customer_cannot_create_a_staycation(): void
    {
        Sanctum::actingAs($this->owner);

        $this->postJson('/api/v1/admin/staycations', [
            'house_name' => 'Rogue Villa',
            'house_description' => 'Should never be created.',
            'house_price' => 1000,
            'house_location' => 'Nowhere',
        ])->assertForbidden();

        $this->assertDatabaseMissing('staycations', ['house_name' => 'Rogue Villa']);
    }

    public function test_staff_cannot_create_a_staycation(): void
    {
        Sanctum::actingAs($this->staff);

        $this->postJson('/api/v1/admin/staycations', [
            'house_name' => 'Rogue Villa',
            'house_description' => 'Should never be created.',
            'house_price' => 1000,
            'house_location' => 'Nowhere',
        ])->assertForbidden();
    }

    public function test_an_admin_can_create_a_staycation(): void
    {
        Sanctum::actingAs($this->admin);

        $this->postJson('/api/v1/admin/staycations', [
            'house_name' => 'Seaside Villa',
            'house_description' => 'A lovely place.',
            'house_price' => 4200,
            'house_location' => 'Batangas',
        ])->assertCreated();

        $this->assertDatabaseHas('staycations', ['house_name' => 'Seaside Villa']);
    }

    public function test_an_admin_may_read_any_customers_booking(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson("/api/v1/admin/bookings/{$this->booking->getKey()}")->assertOk();
    }

    public function test_an_admin_cannot_set_an_arbitrary_payment_status(): void
    {
        Sanctum::actingAs($this->admin);

        $this->putJson("/api/v1/admin/bookings/{$this->booking->getKey()}/payment-status", [
            'payment_status' => 'definitely_paid',
        ])->assertStatus(422)->assertJsonValidationErrors(['payment_status']);
    }
}
