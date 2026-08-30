<?php

namespace Tests\Unit;

use App\Support\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MoneyTest extends TestCase
{
    public function test_it_parses_a_decimal_string_into_centavos(): void
    {
        $this->assertSame(1050000, Money::fromDecimal('10500.00')->centavos());
        $this->assertSame(1, Money::fromDecimal('0.01')->centavos());
        $this->assertSame(250, Money::fromDecimal('2.5')->centavos());
        $this->assertSame(200000, Money::fromDecimal(2000)->centavos());
    }

    public function test_it_treats_a_missing_amount_as_zero(): void
    {
        $this->assertTrue(Money::fromDecimal(null)->isZero());
        $this->assertTrue(Money::fromDecimal('')->isZero());
    }

    public function test_it_rounds_a_third_decimal_half_up(): void
    {
        $this->assertSame(1000, Money::fromDecimal('9.995')->centavos());
        $this->assertSame(999, Money::fromDecimal('9.994')->centavos());
        $this->assertSame(1000, Money::fromDecimal('9.996')->centavos());
    }

    public function test_half_of_an_even_centavo_amount_splits_exactly(): void
    {
        $this->assertSame('50.00', Money::fromDecimal('100.00')->half()->toDecimalString());
    }

    public function test_half_of_an_odd_centavo_amount_rounds_up(): void
    {
        $half = Money::fromDecimal('100.01')->half();

        $this->assertSame('50.01', $half->toDecimalString());
        $this->assertFalse($half->multipliedBy(2)->lessThan(Money::fromDecimal('100.01')));
    }

    public function test_arithmetic_stays_exact_where_floats_would_drift(): void
    {
        $sum = Money::fromDecimal('0.10')->plus(Money::fromDecimal('0.20'));

        $this->assertSame('0.30', $sum->toDecimalString());
        $this->assertTrue($sum->equals(Money::fromDecimal('0.30')));
    }

    public function test_it_renders_a_two_decimal_string(): void
    {
        $this->assertSame('0.00', Money::zero()->toDecimalString());
        $this->assertSame('0.05', Money::fromCentavos(5)->toDecimalString());
        $this->assertSame('1.00', Money::fromCentavos(100)->toDecimalString());
        $this->assertSame('10500.00', Money::fromCentavos(1050000)->toDecimalString());
    }

    // ------------------------------------------------------ exact parsing

    /**
     * The exact value that exposed the reporting defect. 9007199254740993 is
     * 2^53 + 1, the first integer a double cannot hold, so any implementation
     * that routes this string through a float returns 90071992547409.94.
     */
    public function test_an_exact_decimal_string_beyond_the_float_boundary_is_preserved(): void
    {
        $money = Money::fromDecimal('90071992547409.93');

        $this->assertSame(9007199254740993, $money->centavos());
        $this->assertSame('90071992547409.93', $money->toDecimalString());

        // What a float round-trip would have produced instead.
        $this->assertSame('90071992547409.94', number_format((float) '90071992547409.93', 2, '.', ''));
    }

    public function test_adjacent_centavos_stay_distinct_past_the_float_boundary(): void
    {
        $a = Money::fromCentavos(9007199254740993);
        $b = Money::fromCentavos(9007199254740994);

        $this->assertNotSame($a->toDecimalString(), $b->toDecimalString());
        $this->assertSame('90071992547409.94', $b->toDecimalString());
    }

    /**
     * The float that silently changed money by a centavo.
     *
     * 90071992547409.90 has no double; the nearest one is 90071992547409.906,
     * which rounds to ...91. Near that magnitude the gap between representable
     * doubles is wider than a centavo, so the value is refused rather than
     * quietly adjusted.
     */
    public function test_a_float_where_centavos_are_indistinguishable_is_refused(): void
    {
        // What the float actually holds, before any Money involvement.
        $this->assertSame('90071992547409.906', sprintf('%.3F', 90071992547409.90));

        $this->expectException(InvalidArgumentException::class);

        Money::fromDecimal(90071992547409.90);
    }

    /**
     * The same amount, supplied the way large money must be supplied.
     */
    public function test_the_same_amount_is_exact_when_given_as_a_string(): void
    {
        $money = Money::fromDecimal('90071992547409.90');

        $this->assertSame('90071992547409.90', $money->toDecimalString());
        $this->assertSame(9007199254740990, $money->centavos());
    }

    /**
     * The float boundary is the application's DECIMAL(10,2) domain, not 2^53
     * centavos — 2^53 describes integer spacing, not centavo resolution.
     */
    public function test_the_top_of_the_schema_range_is_accepted_as_a_float(): void
    {
        $money = Money::fromDecimal(99999999.99);

        $this->assertSame('99999999.99', $money->toDecimalString());
        $this->assertSame(9999999999, $money->centavos());
    }

    public function test_one_centavo_past_the_schema_range_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromDecimal(100000000.00);
    }

    public function test_a_negative_float_is_refused_by_the_range_guard(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromDecimal(-0.01);
    }

    /**
     * The everyday case this float support exists for: a small value handed
     * back by SQLite or a model cast.
     */
    public function test_an_ordinary_sqlite_style_float_is_still_supported(): void
    {
        $this->assertSame(123456, Money::fromDecimal(1234.56)->centavos());
        $this->assertSame('1234.56', Money::fromDecimal(1234.56)->toDecimalString());
        $this->assertSame('12.34', Money::fromDecimal(12.34)->toDecimalString());
        $this->assertSame('0.01', Money::fromDecimal(0.01)->toDecimalString());
        $this->assertSame('0.00', Money::fromDecimal(0.0)->toDecimalString());
    }

    // ------------------------------------------------- no negative amounts

    public function test_a_negative_amount_is_rejected_outright(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromCentavos(-150);
    }

    public function test_a_negative_decimal_string_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromDecimal('-1.50');
    }

    public function test_a_negative_integer_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromDecimal(-5);
    }

    /**
     * Subtraction that would go below zero is a programming error, not a credit
     * to be carried silently.
     */
    public function test_subtracting_more_than_the_amount_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Money::fromDecimal('100.00')->minus(Money::fromDecimal('120.00'));
    }

    public function test_a_balance_reads_as_nothing_owed_when_overpaid(): void
    {
        $balance = Money::fromDecimal('100.00')->minusOrZero(Money::fromDecimal('120.00'));

        $this->assertTrue($balance->isZero());
        $this->assertSame('0.00', $balance->toDecimalString());
    }

    public function test_a_balance_is_the_difference_when_underpaid(): void
    {
        $balance = Money::fromDecimal('100.00')->minusOrZero(Money::fromDecimal('40.00'));

        $this->assertSame('60.00', $balance->toDecimalString());
    }

    public function test_zero_remains_a_legitimate_amount(): void
    {
        $this->assertSame('0.00', Money::zero()->toDecimalString());
        $this->assertSame('0.00', Money::fromDecimal('0.00')->toDecimalString());
        $this->assertSame('0.00', Money::fromDecimal(0)->toDecimalString());
    }

    public function test_multiplication_scales_exactly(): void
    {
        $this->assertSame('10000.00', Money::fromDecimal('2000.00')->multipliedBy(5)->toDecimalString());
    }
}
