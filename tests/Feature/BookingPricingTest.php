<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Staycation;
use App\Models\User;
use App\Services\BookingPricingService;
use App\Services\PaymentProofService;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsStayDates;
use Tests\TestCase;

class BookingPricingTest extends TestCase
{
    use BuildsStayDates, RefreshDatabase;

    private BookingPricingService $pricing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pricing = app(BookingPricingService::class);
    }

    public function test_it_charges_the_nightly_rate_for_each_night(): void
    {
        $staycation = Staycation::factory()->create(['house_price' => 2000]);

        $quote = $this->pricing->quote(
            $staycation,
            2,
            CarbonImmutable::parse($this->day(9)),
            CarbonImmutable::parse($this->day(14)),
        );

        $this->assertSame(5, $quote['nights']);
        $this->assertSame('10000.00', $quote['total_price']->toDecimalString());
        $this->assertTrue($quote['extra_guest_fee']->isZero());
    }

    public function test_it_adds_the_extra_guest_fee_beyond_the_included_headcount(): void
    {
        $staycation = Staycation::factory()->create(['house_price' => 1000]);

        $quote = $this->pricing->quote(
            $staycation,
            8,
            CarbonImmutable::parse($this->day(9)),
            CarbonImmutable::parse($this->day(11)),
        );

        $this->assertSame(2, $quote['extra_guests']);
        $this->assertSame('1000.00', $quote['extra_guest_fee']->toDecimalString());
        $this->assertSame('3000.00', $quote['total_price']->toDecimalString());
    }

    public function test_the_included_headcount_carries_no_extra_fee(): void
    {
        $staycation = Staycation::factory()->create(['house_price' => 1000]);

        $quote = $this->pricing->quote(
            $staycation,
            BookingPricingService::INCLUDED_GUESTS,
            CarbonImmutable::parse($this->day(9)),
            CarbonImmutable::parse($this->day(10)),
        );

        $this->assertSame(0, $quote['extra_guests']);
        $this->assertSame('1000.00', $quote['total_price']->toDecimalString());
    }

    public function test_a_stay_is_never_priced_at_fewer_than_one_night(): void
    {
        $staycation = Staycation::factory()->create(['house_price' => 1500]);

        $quote = $this->pricing->quote(
            $staycation,
            1,
            CarbonImmutable::parse($this->day(9)),
            CarbonImmutable::parse($this->day(9)),
        );

        $this->assertSame(1, $quote['nights']);
        $this->assertSame('1500.00', $quote['total_price']->toDecimalString());
    }

    public function test_half_payment_is_exactly_half_the_total(): void
    {
        $this->assertSame(
            '5000.00',
            $this->pricing->amountDue(Money::fromDecimal('10000.00'), 'half')->toDecimalString()
        );
    }

    public function test_a_half_payment_of_an_odd_centavo_total_rounds_half_up(): void
    {
        $due = $this->pricing->amountDue(Money::fromDecimal('10000.01'), 'half');

        $this->assertSame('5000.01', $due->toDecimalString());

        // Two halves must never settle for less than the total.
        $this->assertFalse($due->multipliedBy(2)->lessThan(Money::fromDecimal('10000.01')));
    }

    public function test_full_payment_is_the_whole_total(): void
    {
        $this->assertSame(
            '10000.00',
            $this->pricing->amountDue(Money::fromDecimal('10000.00'), 'full')->toDecimalString()
        );
    }

    public function test_the_remaining_balance_is_the_unsettled_portion(): void
    {
        $total = Money::fromDecimal('10000.00');

        $this->assertSame('5000.00', $this->pricing->remainingBalance($total, Money::fromDecimal('5000.00'))->toDecimalString());
        $this->assertSame('0.00', $this->pricing->remainingBalance($total, $total)->toDecimalString());
        $this->assertSame('0.00', $this->pricing->remainingBalance($total, Money::fromDecimal('12000.00'))->toDecimalString());
        $this->assertSame('10000.00', $this->pricing->remainingBalance($total, null)->toDecimalString());
    }

    public function test_the_preview_screen_shows_the_same_total_the_submission_charges(): void
    {
        Storage::fake(PaymentProofService::DISK);

        $staycation = Staycation::factory()->create([
            'house_name' => 'Preview House',
            'house_price' => 2000,
        ]);
        $customer = User::factory()->create();

        $preview = $this->actingAs($customer)->post(route('booking.preview', $staycation), [
            'name' => 'Preview Customer',
            'phone' => '09123456789',
            'guest_number' => 7,
            'startDate' => $this->day(9),
            'endDate' => $this->day(14),
        ]);

        $preview->assertOk();
        $preview->assertSee('10,500.00');
        $this->assertSame('10500.00', $preview->viewData('quote')['total_price']->toDecimalString());

        $this->actingAs($customer)->post(route('booking.submit', $staycation), [
            'guest_number' => 7,
            'startDate' => $this->day(9),
            'endDate' => $this->day(14),
            'payment_type' => 'full',
            'payment_method' => 'gcash',
            'payment_proof' => UploadedFile::fake()->create('proof.jpg', 64, 'image/jpeg'),
            'phone' => '09123456789',
        ])->assertRedirect(route('BookingHistory.index'));

        $this->assertSame('10500.00', Booking::sole()->total_price);
    }

    public function test_a_submitted_booking_is_priced_by_the_server_not_the_form(): void
    {
        Storage::fake(PaymentProofService::DISK);

        $staycation = Staycation::factory()->create(['house_price' => 2000]);
        $customer = User::factory()->create();

        $this->actingAs($customer)
            ->post(route('booking.submit', $staycation), [
                'guest_number' => 7,
                'startDate' => $this->day(9),
                'endDate' => $this->day(14),
                'payment_type' => 'half',
                'payment_method' => 'gcash',
                'payment_proof' => UploadedFile::fake()->create('proof.jpg', 64, 'image/jpeg'),
                'phone' => '09123456789',
                'totalPrice' => 1,
                'total_price' => 1,
                'amount_paid' => 1,
            ])
            ->assertRedirect(route('BookingHistory.index'));

        $booking = Booking::sole();

        // 5 nights x 2000 + 1 extra guest x 500
        $this->assertSame('10500.00', $booking->total_price);
        $this->assertEquals(2000, $booking->price_per_day);

        // The half payment the customer declares is recorded, but nothing is
        // counted as received until an administrator verifies it.
        $this->assertSame('5250.00', $booking->declared_amount);
        $this->assertSame('0.00', $booking->amount_paid);
    }
}
