<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use UnexpectedValueException;

/**
 * How much money a booking has actually brought in.
 *
 * Since Phase 1, `amount_paid` means money an administrator has verified as
 * received, so recognised revenue is simply the sum of that column. Reports must
 * not re-derive it as `total_price / 2`, which is a guess at what a half payment
 * ought to have been rather than a record of what arrived — and which disagrees
 * with the HALF_UP centavo rounding the rest of the system uses.
 *
 * LEGACY COMPATIBILITY
 * --------------------
 * Rows written before that invariant existed did not maintain `amount_paid`:
 * the old `markAsFullyPaid` set `payment_status = 'paid'` and left the amount
 * untouched. Reading `amount_paid` alone would silently under-report them, so
 * those rows still fall back to the old derivation.
 *
 * `declared_amount` is the discriminator: every booking created under the new
 * code sets it, and no row written before this phase has it. That is a read-only
 * test — nothing is rewritten.
 *
 * This fallback is temporary. It must be removed once the pre-production audit
 * and backfill of historical payment data has been completed, after which
 * `amount_paid` is authoritative for every row.
 */
class RevenueReportingService
{
    /**
     * Total verified revenue for a set of bookings.
     *
     * Money never passes through a float here. The database is asked for a sum
     * of whole centavos rather than a sum of decimals, because a decimal sum
     * comes back as a PHP float on SQLite and as a decimal string on MySQL, and
     * the float loses cents once the total passes 2^53 centavos. Summing
     * integers gives an exact integer on both drivers.
     *
     * Split deliberately in two:
     *
     *  - Rows whose amount is already exact — normalised records, and legacy
     *    fully-paid rows — are summed in SQL, which is what keeps this usable on
     *    a large table.
     *  - Legacy half-paid rows are halved individually in PHP. Their half has to
     *    be rounded HALF_UP per row *before* aggregation, and no portable SQL
     *    expression does that: SQLite has no FLOOR by default, and MySQL's
     *    ROUND() on DECIMAL disagrees with SQLite's float ROUND() at exactly
     *    .005. Each row's total is bounded by decimal(10,2), so its own value
     *    round-trips exactly; only the *sum* could overflow, and that is
     *    accumulated in Money. This set is historical and shrinks to nothing
     *    once the payment backfill lands, at which point this branch and the
     *    loop both go.
     *
     * @param  Builder<Booking>  $query
     */
    public function totalVerifiedAmount(Builder $query): Money
    {
        $exactCentavos = (clone $query)->sum(self::exactCentavosExpression($query));

        $total = Money::fromCentavos(self::centavosFromAggregate($exactCentavos));

        $legacyHalfPaidTotals = (clone $query)
            ->whereNull('declared_amount')
            ->where('payment_status', PaymentStatus::HalfPaid->value)
            ->pluck('total_price');

        foreach ($legacyHalfPaidTotals as $bookingTotal) {
            $total = $total->plus(Money::fromDecimal($bookingTotal)->half());
        }

        return $total;
    }

    /**
     * SQL for the rows whose verified amount needs no rounding, in centavos.
     *
     * Legacy half-paid rows deliberately contribute zero here; they are added
     * per row by totalVerifiedAmount().
     *
     * The amount is scaled to centavos and cast to an integer inside the query
     * so that SUM() is an integer sum. ROUND() before the cast matters on
     * SQLite, where the stored value is a float and `12.34 * 100` can land on
     * 1233.9999999999998 — casting that directly would truncate a centavo away.
     * On MySQL the value is an exact DECIMAL, so the ROUND() is a no-op.
     *
     * @param  Builder<Booking>  $query
     */
    public static function exactCentavosExpression(Builder $query): Expression
    {
        $paid = PaymentStatus::Paid->value;

        // MySQL and MariaDB have no INTEGER cast target; SQLite has no SIGNED.
        $integerType = in_array($query->getConnection()->getDriverName(), ['mysql', 'mariadb'], true)
            ? 'SIGNED'
            : 'INTEGER';

        return DB::raw(
            "CAST(ROUND(
                CASE
                    WHEN declared_amount IS NOT NULL THEN COALESCE(amount_paid, 0)
                    WHEN payment_status = '{$paid}' THEN total_price
                    ELSE 0
                END * 100
            ) AS {$integerType})"
        );
    }

    /**
     * The aggregate as a whole number of centavos.
     *
     * SQLite returns a PHP int; MySQL returns the BIGINT sum as a numeric
     * string. The string is parsed digit-wise, never via a float: casting
     * "9007199254740993" through a double yields 9007199254740992, which is the
     * exact centavo this whole method exists to stop losing.
     *
     * A float is refused outright, with no exception for one that happens to
     * hold a whole number. The aggregate contract is integer centavos — a PHP
     * int from SQLite, an exact numeric string from MySQL — so a float means the
     * query stopped honouring that contract. 100.0 looking harmless today is no
     * argument for accepting it: the same path would wave through a value that
     * had already lost centavos before it reached us, which is the exact
     * corruption this method exists to stop. Fail loudly and fix the query.
     *
     * Scientific notation, fractional centavos and malformed strings are
     * refused for the same reason.
     *
     * Public so the conversion can be tested at the precision boundary directly;
     * reaching 2^53 centavos through the database would need roughly 900,000
     * bookings, since decimal(10,2) caps a single one at 99,999,999.99.
     */
    public static function centavosFromAggregate(mixed $aggregate): int
    {
        if (is_int($aggregate)) {
            return $aggregate;
        }

        if (is_float($aggregate)) {
            throw new UnexpectedValueException(
                'Revenue aggregate arrived as a float ('.var_export($aggregate, true).'). '
                .'The aggregate must be a whole number of centavos; a float means the query '
                .'is no longer summing integer centavos.'
            );
        }

        if ($aggregate === null || $aggregate === '') {
            return 0;
        }

        $value = (string) $aggregate;

        if (! preg_match('/^(-?\d+)(?:\.0*)?$/', $value, $matches)) {
            throw new UnexpectedValueException(
                "Revenue aggregate [{$value}] is not a whole number of centavos."
            );
        }

        return (int) $matches[1];
    }

    /**
     * The same rule in PHP, in centavos, for report rows assembled in memory.
     */
    public function verifiedAmountFor(Booking $booking): Money
    {
        if ($booking->declared_amount !== null) {
            return $booking->amountPaid();
        }

        return match ($booking->paymentStatus()) {
            PaymentStatus::Paid => $booking->totalPrice(),
            PaymentStatus::HalfPaid => $booking->totalPrice()->half(),
            default => Money::zero(),
        };
    }
}
