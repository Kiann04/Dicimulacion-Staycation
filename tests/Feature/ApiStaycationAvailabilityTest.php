<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Http\Controllers\Api\V1\StaycationAvailabilityController;
use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\Staycation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\BuildsStayDates;
use Tests\TestCase;

/**
 * The availability endpoint must answer exactly as the Phase 1 service does,
 * including the checkout-day rule that lets one guest arrive on the day another
 * leaves. Each case here mirrors a case in BookingAvailabilityTest, so a drift
 * between the API and the domain surfaces as a failure rather than as a double
 * booking.
 */
class ApiStaycationAvailabilityTest extends TestCase
{
    use BuildsStayDates, RefreshDatabase;

    private Staycation $staycation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->staycation = Staycation::factory()->create();
    }

    private function check(string $startDate, string $endDate, ?Staycation $staycation = null): TestResponse
    {
        $staycation ??= $this->staycation;

        return $this->getJson(sprintf(
            '/api/v1/staycations/%d/availability?start_date=%s&end_date=%s',
            $staycation->getKey(),
            urlencode($startDate),
            urlencode($endDate),
        ));
    }

    private function occupy(int $startOffset, int $endOffset, ?Staycation $staycation = null): Booking
    {
        return Booking::factory()
            ->for($staycation ?? $this->staycation)
            ->forDates($this->day($startOffset), $this->day($endOffset))
            ->status(BookingStatus::Confirmed)
            ->create();
    }

    public function test_a_free_range_is_reported_available(): void
    {
        $this->check($this->day(10), $this->day(14))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('data.staycation_id', $this->staycation->getKey())
            ->assertJsonPath('data.start_date', $this->day(10))
            ->assertJsonPath('data.end_date', $this->day(14))
            ->assertJsonPath('data.nights', 4)
            ->assertJsonPath('data.available', true)
            ->assertJsonPath('data.unavailable_reasons', []);
    }

    /**
     * An availability answer is a read of the calendar, never a hold. The flag
     * is asserted so the contract cannot quietly start implying otherwise.
     */
    public function test_an_available_answer_states_that_it_reserves_nothing(): void
    {
        $this->check($this->day(10), $this->day(14))
            ->assertOk()
            ->assertJsonPath('data.reserves_inventory', false);

        $this->assertSame(0, Booking::query()->count());
    }

    public function test_a_range_overlapping_a_booking_is_refused_with_a_reason(): void
    {
        $this->occupy(9, 14);

        $this->check($this->day(11), $this->day(13))
            ->assertOk()
            ->assertJsonPath('data.available', false)
            ->assertJsonPath('data.unavailable_reasons', [
                StaycationAvailabilityController::REASON_BOOKING_CONFLICT,
            ]);
    }

    public function test_a_stay_may_begin_on_the_previous_checkout_day(): void
    {
        $this->occupy(9, 14);

        $this->check($this->day(14), $this->day(19))
            ->assertOk()
            ->assertJsonPath('data.available', true);
    }

    public function test_a_stay_may_end_on_the_next_arrival_day(): void
    {
        $this->occupy(14, 19);

        $this->check($this->day(9), $this->day(14))
            ->assertOk()
            ->assertJsonPath('data.available', true);
    }

    public function test_a_cancelled_booking_releases_its_dates(): void
    {
        Booking::factory()
            ->for($this->staycation)
            ->forDates($this->day(9), $this->day(14))
            ->status(BookingStatus::Cancelled)
            ->create();

        $this->check($this->day(10), $this->day(13))
            ->assertOk()
            ->assertJsonPath('data.available', true);
    }

    /**
     * A blocked range is inclusive of its end day, unlike a booking, so a stay
     * arriving on that day is still refused.
     */
    public function test_a_blocked_range_is_refused_inclusive_of_its_final_day(): void
    {
        BlockedDate::factory()->create([
            'staycation_id' => $this->staycation->getKey(),
            'start_date' => $this->day(10),
            'end_date' => $this->day(12),
        ]);

        $this->check($this->day(12), $this->day(15))
            ->assertOk()
            ->assertJsonPath('data.available', false)
            ->assertJsonPath('data.unavailable_reasons', [
                StaycationAvailabilityController::REASON_BLOCKED_DATES,
            ]);

        $this->check($this->day(13), $this->day(15))
            ->assertOk()
            ->assertJsonPath('data.available', true);
    }

    public function test_a_property_taken_offline_is_never_available(): void
    {
        $offline = Staycation::factory()->unavailable()->create();

        $this->check($this->day(10), $this->day(14), $offline)
            ->assertOk()
            ->assertJsonPath('data.available', false)
            ->assertJsonPath('data.unavailable_reasons', [
                StaycationAvailabilityController::REASON_PROPERTY_UNAVAILABLE,
            ]);
    }

    public function test_every_applicable_reason_is_reported_at_once(): void
    {
        $offline = Staycation::factory()->unavailable()->create();

        $this->occupy(9, 14, $offline);

        BlockedDate::factory()->create([
            'staycation_id' => $offline->getKey(),
            'start_date' => $this->day(10),
            'end_date' => $this->day(12),
        ]);

        $this->check($this->day(10), $this->day(13), $offline)
            ->assertOk()
            ->assertJsonPath('data.available', false)
            ->assertJsonPath('data.unavailable_reasons', [
                StaycationAvailabilityController::REASON_PROPERTY_UNAVAILABLE,
                StaycationAvailabilityController::REASON_BOOKING_CONFLICT,
                StaycationAvailabilityController::REASON_BLOCKED_DATES,
            ]);
    }

    public function test_it_requires_both_dates(): void
    {
        $this->getJson("/api/v1/staycations/{$this->staycation->getKey()}/availability")
            ->assertStatus(422)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonValidationErrors(['start_date', 'end_date']);
    }

    public function test_it_refuses_a_departure_that_is_not_after_the_arrival(): void
    {
        $this->check($this->day(10), $this->day(10))
            ->assertStatus(422)
            ->assertJsonValidationErrors('end_date');

        $this->check($this->day(10), $this->day(8))
            ->assertStatus(422)
            ->assertJsonValidationErrors('end_date');
    }

    public function test_it_refuses_an_arrival_in_the_past(): void
    {
        $this->check($this->day(-1), $this->day(3))
            ->assertStatus(422)
            ->assertJsonValidationErrors('start_date');
    }

    public function test_it_refuses_a_date_that_is_not_iso_formatted(): void
    {
        $this->check('next friday', $this->day(14))
            ->assertStatus(422)
            ->assertJsonValidationErrors('start_date');

        $this->check('31-12-2099', '01-01-2100')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['start_date', 'end_date']);
    }

    public function test_a_validation_failure_uses_the_standard_error_envelope(): void
    {
        $payload = $this->check($this->day(10), $this->day(10))
            ->assertStatus(422)
            ->json();

        $this->assertArrayHasKey('message', $payload);
        $this->assertArrayHasKey('errors', $payload);
        $this->assertIsArray($payload['errors']['end_date']);
    }

    public function test_it_returns_json_for_a_staycation_that_does_not_exist(): void
    {
        $this->getJson('/api/v1/staycations/999999/availability?start_date='.$this->day(10).'&end_date='.$this->day(14))
            ->assertNotFound()
            ->assertExactJson(['message' => 'Staycation not found.']);
    }
}
