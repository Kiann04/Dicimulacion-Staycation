<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Staycation;
use App\Models\User;
use App\Services\BookingPaymentService;
use App\Services\RevenueReportingService;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsStayDates;
use Tests\TestCase;
use UnexpectedValueException;

/**
 * Recognised revenue is verified money received, not a re-derivation of what a
 * half payment ought to have been.
 */
class RevenueReportingTest extends TestCase
{
    use BuildsStayDates, RefreshDatabase;

    private RevenueReportingService $revenue;

    private Staycation $staycation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->revenue = app(RevenueReportingService::class);
        $this->staycation = Staycation::factory()->create();
        $this->actingAs(User::factory()->admin()->create());
    }

    /**
     * A record written under the verified-payment invariant always has
     * declared_amount set.
     */
    private function normalisedBooking(string $total, ?PaymentStatus $verifyAs): Booking
    {
        $booking = Booking::factory()->for($this->staycation)->create([
            'total_price' => $total,
            'amount_paid' => 0,
            'declared_amount' => $total,
            'payment_status' => PaymentStatus::Pending->value,
        ]);

        if ($verifyAs !== null) {
            app(BookingPaymentService::class)->verifyPayment($booking, $verifyAs);
        }

        return $booking->refresh();
    }

    /**
     * A row predating the invariant: declared_amount was never populated, and
     * amount_paid was not maintained.
     */
    private function legacyBooking(string $total, PaymentStatus $status, ?string $amountPaid): Booking
    {
        return Booking::factory()->for($this->staycation)->create([
            'total_price' => $total,
            'amount_paid' => $amountPaid,
            'declared_amount' => null,
            'payment_status' => $status->value,
        ]);
    }

    private function totalRevenue(): string
    {
        return $this->revenue->totalVerifiedAmount(Booking::query())->toDecimalString();
    }

    // ------------------------------------------------- normalised records

    public function test_a_fully_paid_booking_recognises_its_whole_total(): void
    {
        $this->normalisedBooking('10000.00', PaymentStatus::Paid);

        $this->assertEquals(10000, $this->totalRevenue());
    }

    public function test_a_half_paid_booking_recognises_only_what_was_verified(): void
    {
        $this->normalisedBooking('10000.00', PaymentStatus::HalfPaid);

        $this->assertEquals(5000, $this->totalRevenue());
    }

    public function test_an_unverified_booking_recognises_nothing(): void
    {
        $this->normalisedBooking('10000.00', null);

        $this->assertEquals(0, $this->totalRevenue());
    }

    public function test_a_declared_amount_alone_is_not_revenue(): void
    {
        // The customer says they sent the full amount; nobody has checked.
        $booking = $this->normalisedBooking('10000.00', null);

        $this->assertSame('10000.00', $booking->declared_amount);
        $this->assertEquals(0, $this->totalRevenue());
    }

    public function test_an_odd_centavo_half_payment_recognises_the_rounded_amount(): void
    {
        $this->normalisedBooking('10000.01', PaymentStatus::HalfPaid);

        // HALF_UP: 5000.005 -> 5000.01, matching what was actually recorded.
        $this->assertEquals(5000.01, $this->totalRevenue());
        $this->assertSame('5000.01', Booking::sole()->amount_paid);
    }

    public function test_the_php_rule_matches_the_sql_rule(): void
    {
        $booking = $this->normalisedBooking('10000.01', PaymentStatus::HalfPaid);

        $this->assertSame('5000.01', $this->revenue->verifiedAmountFor($booking)->toDecimalString());
        $this->assertSame($this->totalRevenue(), $this->revenue->verifiedAmountFor($booking)->toDecimalString());
    }

    public function test_revenue_follows_amount_paid_not_half_the_total(): void
    {
        // A normalised row whose verified amount deliberately differs from half.
        $booking = $this->normalisedBooking('10000.00', PaymentStatus::HalfPaid);
        $booking->forceFill(['amount_paid' => '3000.00'])->save();

        $this->assertEquals(3000, $this->totalRevenue());
    }

    // ------------------------------------------------------------- legacy

    public function test_a_legacy_paid_row_is_still_recognised_in_full(): void
    {
        // The old markAsFullyPaid left amount_paid at the half figure.
        $this->legacyBooking('10000.00', PaymentStatus::Paid, '5000.00');

        $this->assertEquals(10000, $this->totalRevenue());
    }

    public function test_a_legacy_half_paid_row_falls_back_to_the_old_derivation(): void
    {
        $this->legacyBooking('10000.00', PaymentStatus::HalfPaid, null);

        $this->assertEquals(5000, $this->totalRevenue());
    }

    public function test_a_legacy_unpaid_row_recognises_nothing(): void
    {
        $this->legacyBooking('10000.00', PaymentStatus::Unpaid, null);

        $this->assertEquals(0, $this->totalRevenue());
    }

    public function test_legacy_and_normalised_rows_add_up_together(): void
    {
        $this->legacyBooking('10000.00', PaymentStatus::Paid, '5000.00');   // 10000 legacy
        $this->normalisedBooking('4000.00', PaymentStatus::HalfPaid);        //  2000 verified

        $this->assertEquals(12000, $this->totalRevenue());
    }

    /**
     * Each legacy half must be rounded HALF_UP on its own before anything is
     * added up. Three bookings at 10000.01 are 5000.01 each — 15000.03 — not
     * ROUND(30000.03 / 2, 2) = 15000.02, which is what a naive aggregate gives.
     */
    public function test_multiple_odd_centavo_legacy_rows_round_per_row(): void
    {
        $this->legacyBooking('10000.01', PaymentStatus::HalfPaid, null);
        $this->legacyBooking('10000.01', PaymentStatus::HalfPaid, null);
        $this->legacyBooking('10000.01', PaymentStatus::HalfPaid, null);

        $this->assertSame('15000.03', $this->totalRevenue());
    }

    public function test_a_long_run_of_odd_centavo_legacy_rows_stays_exact(): void
    {
        foreach (range(1, 7) as $ignored) {
            $this->legacyBooking('0.01', PaymentStatus::HalfPaid, null);
        }

        // Half of 0.01 rounds HALF_UP to 0.01, seven times over.
        $this->assertSame('0.07', $this->totalRevenue());
    }

    public function test_odd_centavo_legacy_and_normalised_rows_stay_exact_together(): void
    {
        $this->legacyBooking('10000.01', PaymentStatus::HalfPaid, null);   // 5000.01
        $this->legacyBooking('999.99', PaymentStatus::HalfPaid, null);     //  500.00 (499.995 -> 500.00)
        $this->normalisedBooking('10000.01', PaymentStatus::HalfPaid);     // 5000.01

        $this->assertSame('10500.02', $this->totalRevenue());
    }

    // ------------------------------------------------- exact-value handling

    /**
     * The value Codex found. 9007199254740993 centavos is 2^53 + 1, the first
     * integer a double cannot represent, so the old
     * `Money::fromDecimal(number_format((float) $sum, 2))` returned
     * 90071992547409.94. The parser itself was always exact for strings; the
     * float conversion in front of it was the defect.
     */
    public function test_an_exact_decimal_string_survives_the_money_parser(): void
    {
        $money = Money::fromDecimal('90071992547409.93');

        $this->assertSame(9007199254740993, $money->centavos());
        $this->assertSame('90071992547409.93', $money->toDecimalString());
    }

    /**
     * Guards the specific conversion that was removed: routing that same exact
     * string through a float and back changes it. If this ever stops being true
     * the regression test above has stopped proving anything.
     */
    public function test_the_removed_float_conversion_really_did_corrupt_the_value(): void
    {
        $viaFloat = number_format((float) '90071992547409.93', 2, '.', '');

        $this->assertSame('90071992547409.94', $viaFloat);
        $this->assertNotSame('90071992547409.93', $viaFloat);
    }

    /**
     * The exact site of the defect, at the exact value Codex found.
     *
     * MySQL returns a BIGINT SUM as a numeric string. The old code cast that to
     * a float before handing it to Money; 9007199254740993 is 2^53 + 1, the
     * first integer a double cannot hold, so a centavo vanished. This fails
     * under any float-based conversion.
     */
    public function test_a_boundary_aggregate_string_converts_without_losing_a_centavo(): void
    {
        $fromDriver = '9007199254740993';

        $this->assertSame(
            9007199254740993,
            RevenueReportingService::centavosFromAggregate($fromDriver)
        );

        // What the removed conversion would have produced.
        $this->assertSame(9007199254740992, (int) (float) $fromDriver);
    }

    public function test_a_boundary_aggregate_reaches_money_intact(): void
    {
        $money = Money::fromCentavos(
            RevenueReportingService::centavosFromAggregate('9007199254740993')
        );

        $this->assertSame('90071992547409.93', $money->toDecimalString());
    }

    public function test_an_integer_aggregate_passes_through_untouched(): void
    {
        $this->assertSame(
            9007199254740993,
            RevenueReportingService::centavosFromAggregate(9007199254740993)
        );
    }

    /**
     * MySQL can return a DECIMAL sum with a zero fraction attached.
     */
    public function test_a_zero_fraction_on_the_aggregate_is_accepted(): void
    {
        $this->assertSame(29999999997, RevenueReportingService::centavosFromAggregate('29999999997.0000'));
        $this->assertSame(0, RevenueReportingService::centavosFromAggregate('0.00'));
    }

    public function test_an_empty_aggregate_is_nothing(): void
    {
        $this->assertSame(0, RevenueReportingService::centavosFromAggregate(null));
        $this->assertSame(0, RevenueReportingService::centavosFromAggregate(''));
        $this->assertSame(0, RevenueReportingService::centavosFromAggregate(0));
    }

    /**
     * A float reaching this point means the query stopped returning whole
     * centavos. Coercing it would be the corruption this guards against.
     */
    public function test_a_float_aggregate_is_refused_rather_than_coerced(): void
    {
        $this->expectException(UnexpectedValueException::class);

        RevenueReportingService::centavosFromAggregate(299999999.97);
    }

    /**
     * Floats holding a whole number are refused too.
     *
     * `100.0` stringifies to "100" and would sail through a digit check, but the
     * aggregate contract is integer centavos: a float means the query stopped
     * honouring it, and the next such value may already have lost centavos.
     *
     * @return array<string, array{0: float}>
     */
    public static function integerValuedFloatProvider(): array
    {
        return [
            'one hundred' => [100.0],
            'zero' => [0.0],
            'one' => [1.0],
            'large whole' => [90071992547409.0],
        ];
    }

    /**
     * @dataProvider integerValuedFloatProvider
     */
    public function test_an_integer_valued_float_aggregate_is_still_refused(float $aggregate): void
    {
        $this->expectException(UnexpectedValueException::class);

        RevenueReportingService::centavosFromAggregate($aggregate);
    }

    /**
     * Zero as a float must not be mistaken for "no rows".
     */
    public function test_a_zero_float_aggregate_is_refused_not_treated_as_empty(): void
    {
        $this->expectException(UnexpectedValueException::class);

        RevenueReportingService::centavosFromAggregate(0.0);
    }

    /**
     * The integer and string forms of the same whole number stay accepted, so
     * the guard rejects the float type rather than the value.
     */
    public function test_the_integer_and_string_forms_remain_accepted(): void
    {
        $this->assertSame(100, RevenueReportingService::centavosFromAggregate(100));
        $this->assertSame(100, RevenueReportingService::centavosFromAggregate('100'));
        $this->assertSame(100, RevenueReportingService::centavosFromAggregate('100.0'));
        $this->assertSame(0, RevenueReportingService::centavosFromAggregate(0));
    }

    public function test_a_fractional_aggregate_is_refused(): void
    {
        $this->expectException(UnexpectedValueException::class);

        RevenueReportingService::centavosFromAggregate('12345.67');
    }

    public function test_a_scientific_notation_aggregate_is_refused(): void
    {
        $this->expectException(UnexpectedValueException::class);

        RevenueReportingService::centavosFromAggregate('9.007199254740993E+15');
    }

    /**
     * Money at and beyond the IEEE-754 integer boundary.
     *
     * Reaching 2^53 centavos by summing rows would need ~900,000 bookings, since
     * decimal(10,2) caps one at 99,999,999.99. The guarantee is proven directly
     * instead: centavos are carried as PHP integers, which are exact to
     * 9.2 x 10^18, so the boundary a double trips over is simply not a boundary
     * for this representation.
     */
    public function test_money_is_exact_at_and_beyond_the_float_boundary(): void
    {
        $boundary = Money::fromCentavos(9007199254740993); // 2^53 + 1

        $this->assertSame('90071992547409.93', $boundary->toDecimalString());
        $this->assertSame(9007199254740993, $boundary->centavos());

        // Adding one centavo at a time stays exact, where a double would stall.
        $next = $boundary->plus(Money::fromCentavos(1));

        $this->assertSame(9007199254740994, $next->centavos());
        $this->assertNotSame($boundary->toDecimalString(), $next->toDecimalString());
    }

    /**
     * The property that makes the above reachable from the database: the
     * aggregate itself must arrive as a whole number of centavos, never a float.
     * A decimal SUM returns a double on SQLite, which is where cents were lost.
     */
    public function test_the_aggregate_is_a_whole_number_of_centavos_not_a_float(): void
    {
        $this->normalisedBooking('99999999.99', PaymentStatus::Paid);
        $this->normalisedBooking('0.01', PaymentStatus::Paid);

        $aggregate = Booking::query()->sum(
            RevenueReportingService::exactCentavosExpression(Booking::query())
        );

        $this->assertIsNotFloat($aggregate);
        $this->assertMatchesRegularExpression('/^\d+$/', (string) $aggregate);
        $this->assertSame('10000000000', (string) $aggregate);
        $this->assertSame('100000000.00', $this->totalRevenue());
    }

    public function test_a_multi_row_total_stays_exact(): void
    {
        foreach (range(1, 12) as $ignored) {
            $this->normalisedBooking('99999999.99', PaymentStatus::Paid);
        }

        // 12 x 9,999,999,999 centavos = 119,999,999,988 centavos.
        $this->assertSame('1199999999.88', $this->totalRevenue());
    }

    /**
     * A cent must not be truncated away when SQLite stores the amount as a
     * float: 12.34 * 100 is 1233.9999999999998 there, and casting without
     * rounding first would lose a centavo per row.
     */
    public function test_scaling_to_centavos_never_truncates_a_cent(): void
    {
        foreach (['12.34', '0.07', '19.99', '1.01', '99.29'] as $amount) {
            $this->normalisedBooking($amount, PaymentStatus::Paid);
        }

        // 1234 + 7 + 1999 + 101 + 9929 = 13270 centavos.
        $this->assertSame('132.70', $this->totalRevenue());
    }

    public function test_an_empty_set_reports_nothing_rather_than_failing(): void
    {
        $this->assertSame('0.00', $this->totalRevenue());
    }

    // ------------------------------------------------------------ endpoint

    public function test_the_annual_report_totals_in_whole_centavos(): void
    {
        $this->normalisedBooking('10000.01', PaymentStatus::HalfPaid);
        $this->normalisedBooking('10000.01', PaymentStatus::HalfPaid);
        $this->normalisedBooking('10000.01', PaymentStatus::HalfPaid);

        $response = $this->post(route('admin.reports.generate'), [
            'report_type' => 'annual',
            'report_year' => now()->year,
        ]);

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }

    public function test_the_annual_report_revenue_is_a_deterministic_cent_string(): void
    {
        $this->normalisedBooking('10000.01', PaymentStatus::HalfPaid);
        $this->normalisedBooking('10000.01', PaymentStatus::HalfPaid);

        $captured = null;

        \Illuminate\Support\Facades\View::composer('admin.reports_pdf', function ($view) use (&$captured): void {
            $captured = $view->getData();
        });

        $this->post(route('admin.reports.generate'), [
            'report_type' => 'annual',
            'report_year' => now()->year,
        ])->assertOk();

        $this->assertNotNull($captured);
        $this->assertSame('10000.02', $captured['totalRevenue']);
        $this->assertIsString($captured['totalRevenue']);
    }

    public function test_the_dashboard_reports_verified_revenue(): void
    {
        $this->normalisedBooking('10000.00', PaymentStatus::HalfPaid);

        $response = $this->get(route('admin.dashboard'));

        $response->assertOk();
        $this->assertEquals(5000, $response->viewData('totalRevenue'));
    }
}
