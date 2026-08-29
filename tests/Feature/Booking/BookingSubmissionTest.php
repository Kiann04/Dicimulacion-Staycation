<?php

namespace Tests\Feature\Booking;

use App\Enums\BookingStatus;
use App\Enums\PaymentRecordStatus;
use App\Enums\PaymentStatus;
use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\Staycation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * End-to-end booking submission through the v1 API.
 *
 * The server is authoritative: these tests assert that prices are recomputed
 * server-side, that a client cannot inject its own totals, and that a range which
 * became unavailable after the customer opened the form is rejected at submission.
 */
class BookingSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private Staycation $staycation;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->staycation = Staycation::factory()->pricedAt(3000)->create();
        $this->customer = User::factory()->create();
    }

    /**
     * A JPEG built without GD, which is not installed in every environment.
     */
    private function proof(): UploadedFile
    {
        return UploadedFile::fake()->create('proof.jpg', 120, 'image/jpeg');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'staycation_id' => $this->staycation->getKey(),
            'start_date' => '2026-10-10',
            'end_date' => '2026-10-13',
            'guest_number' => 4,
            'phone' => '09171234567',
            'payment_type' => 'half',
            'payment_method' => 'gcash',
            'payment_proof' => $this->proof(),
            'transaction_number' => 'TXN-12345',
        ], $overrides);
    }

    public function test_a_customer_can_submit_a_booking(): void
    {
        Sanctum::actingAs($this->customer);

        $response = $this->postJson('/api/v1/bookings', $this->payload());

        $response->assertCreated()
            ->assertJsonPath('data.status', BookingStatus::Pending->value)
            ->assertJsonPath('data.payment.status', PaymentStatus::Pending->value)
            ->assertJsonPath('data.stay.nights', 3)
            // 3 nights x 3000, no extra-guest fee at 4 guests.
            ->assertJsonPath('data.pricing.total_price', '9000.00')
            ->assertJsonPath('data.pricing.amount_paid', '0.00');

        $this->assertDatabaseHas('bookings', [
            'user_id' => $this->customer->getKey(),
            'staycation_id' => $this->staycation->getKey(),
            'total_price' => '9000.00',
            'status' => BookingStatus::Pending->value,
        ]);
    }

    public function test_extra_guests_beyond_the_free_threshold_are_charged(): void
    {
        Sanctum::actingAs($this->customer);

        // 8 guests = 2 beyond the threshold of 6, at 500 each.
        $response = $this->postJson('/api/v1/bookings', $this->payload(['guest_number' => 8]));

        $response->assertCreated()
            ->assertJsonPath('data.pricing.total_price', '10000.00');
    }

    public function test_the_server_ignores_prices_supplied_by_the_client(): void
    {
        Sanctum::actingAs($this->customer);

        $response = $this->postJson('/api/v1/bookings', $this->payload([
            'total_price' => '1.00',
            'amount_paid' => '1.00',
            'price_per_day' => '1.00',
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.pricing.total_price', '9000.00')
            ->assertJsonPath('data.pricing.price_per_night', '3000.00');

        $this->assertDatabaseMissing('bookings', ['total_price' => '1.00']);
    }

    public function test_a_half_payment_records_a_pending_deposit_for_half_the_total(): void
    {
        Sanctum::actingAs($this->customer);

        $this->postJson('/api/v1/bookings', $this->payload(['payment_type' => 'half']))
            ->assertCreated();

        $this->assertDatabaseHas('payments', [
            'amount' => '4500.00',
            'type' => 'deposit',
            'status' => PaymentRecordStatus::Pending->value,
        ]);
    }

    public function test_a_full_payment_records_a_pending_payment_for_the_whole_total(): void
    {
        Sanctum::actingAs($this->customer);

        $this->postJson('/api/v1/bookings', $this->payload(['payment_type' => 'full']))
            ->assertCreated();

        $this->assertDatabaseHas('payments', [
            'amount' => '9000.00',
            'type' => 'full',
            'status' => PaymentRecordStatus::Pending->value,
        ]);
    }

    public function test_amount_paid_is_not_credited_until_an_admin_verifies_the_proof(): void
    {
        Sanctum::actingAs($this->customer);

        $this->postJson('/api/v1/bookings', $this->payload())->assertCreated();

        // The customer says they paid; nothing is credited on their word alone.
        $this->assertSame('0.00', (string) Booking::first()->amount_paid);
    }

    public function test_submission_is_rejected_when_the_dates_collide_with_a_blocking_booking(): void
    {
        Booking::factory()
            ->for($this->staycation)
            ->forDates('2026-10-11', '2026-10-14')
            ->confirmed()
            ->create();

        Sanctum::actingAs($this->customer);

        $this->postJson('/api/v1/bookings', $this->payload())
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'dates_unavailable')
            ->assertJsonCount(1, 'conflicts');

        $this->assertSame(1, Booking::count());
    }

    public function test_submission_succeeds_when_the_only_overlapping_booking_is_cancelled(): void
    {
        Booking::factory()
            ->for($this->staycation)
            ->forDates('2026-10-10', '2026-10-13')
            ->cancelled()
            ->create();

        Sanctum::actingAs($this->customer);

        $this->postJson('/api/v1/bookings', $this->payload())->assertCreated();
    }

    public function test_submission_is_rejected_for_blocked_dates(): void
    {
        BlockedDate::factory()
            ->for($this->staycation)
            ->forDates('2026-10-12', '2026-10-14')
            ->create(['reason' => 'Maintenance']);

        Sanctum::actingAs($this->customer);

        $this->postJson('/api/v1/bookings', $this->payload())
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'dates_unavailable');
    }

    public function test_a_back_to_back_booking_on_the_checkout_day_is_accepted(): void
    {
        Booking::factory()
            ->for($this->staycation)
            ->forDates('2026-10-07', '2026-10-10')
            ->confirmed()
            ->create();

        Sanctum::actingAs($this->customer);

        $this->postJson('/api/v1/bookings', $this->payload())->assertCreated();
    }

    public function test_a_booking_cannot_be_made_on_an_unavailable_staycation(): void
    {
        $this->staycation->update(['house_availability' => 'unavailable']);

        Sanctum::actingAs($this->customer);

        $this->postJson('/api/v1/bookings', $this->payload())
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'staycation_not_bookable');
    }

    public function test_the_payment_proof_is_stored_privately_and_not_exposed(): void
    {
        Sanctum::actingAs($this->customer);

        $response = $this->postJson('/api/v1/bookings', $this->payload())->assertCreated();

        $storedPath = Booking::first()->payment_proof;

        // Written to the private disk under an unguessable name...
        Storage::disk('local')->assertExists($storedPath);
        $this->assertStringStartsWith('payment_proofs/', $storedPath);
        $this->assertDoesNotMatchRegularExpression('/proof\.jpg$/', $storedPath);

        // ...and the path itself never appears in the response body.
        $response->assertJsonMissing(['payment_proof' => $storedPath]);
        $this->assertStringNotContainsString($storedPath, $response->getContent());
    }

    public function test_submission_requires_authentication(): void
    {
        $this->postJson('/api/v1/bookings', $this->payload())
            ->assertUnauthorized()
            ->assertJsonPath('error_code', 'unauthenticated');

        $this->assertSame(0, Booking::count());
    }

    public function test_validation_rejects_a_missing_proof_and_a_reversed_date_range(): void
    {
        Sanctum::actingAs($this->customer);

        $this->postJson('/api/v1/bookings', $this->payload([
            'payment_proof' => null,
            'start_date' => '2026-10-13',
            'end_date' => '2026-10-10',
        ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['payment_proof', 'end_date']);
    }

    public function test_validation_rejects_more_guests_than_the_staycation_holds(): void
    {
        Sanctum::actingAs($this->customer);

        $this->postJson('/api/v1/bookings', $this->payload(['guest_number' => 99]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['guest_number']);
    }

    public function test_a_stay_in_the_past_is_rejected(): void
    {
        Sanctum::actingAs($this->customer);

        $this->postJson('/api/v1/bookings', $this->payload([
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->subDays(2)->toDateString(),
        ]))
            ->assertStatus(422)
            ->assertJsonPath('error_code', 'invalid_dates');
    }
}
