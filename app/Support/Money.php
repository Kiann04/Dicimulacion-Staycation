<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * An immutable peso amount held as an integer number of centavos.
 *
 * Monetary values must never round-trip through binary floating point: 0.1 + 0.2
 * is not 0.3 in a float, and a half payment of an odd centavo total would drift.
 * Every amount is parsed into centavos once, all arithmetic is integer, and the
 * only float conversion is `toFloat()`, which exists for display and JSON.
 *
 * Rounding is HALF_UP at two decimals throughout.
 */
final class Money
{
    /**
     * The largest amount a float argument may carry: the ceiling of this
     * application's persisted money columns, DECIMAL(10,2).
     *
     * Deliberately *not* 2^53 centavos. That threshold describes where a double
     * stops naming consecutive integers, which is the wrong question — what
     * matters is whether a double can still tell one centavo from the next.
     * Near 9 x 10^13 pesos the spacing between representable doubles is already
     * larger than a centavo, so 90071992547409.90 is stored as
     * 90071992547409.906 and rounds to ...91: money changed by a centavo with
     * nothing to signal it.
     *
     * Within DECIMAL(10,2) the spacing is around 1.5 x 10^-8 pesos, roughly a
     * millionth of a centavo, so rounding a float to two decimals is
     * unambiguous. That is the whole justification for accepting floats at all.
     */
    private const MAXIMUM_FLOAT_AMOUNT = 99999999.99;

    private function __construct(private readonly int $centavos)
    {
        // This application has no concept of a negative amount: prices, payments
        // and balances are all zero or more. Rejecting negatives here means no
        // caller has to reason about what halving or formatting one would do,
        // and a sign error surfaces at its source rather than as a strange total
        // three layers away.
        if ($centavos < 0) {
            throw new InvalidArgumentException(
                'A monetary amount cannot be negative; got '.$centavos.' centavos.'
            );
        }
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public static function fromCentavos(int $centavos): self
    {
        return new self($centavos);
    }

    /**
     * Parse a decimal amount without going through a float.
     *
     * A decimal string is parsed digit-wise, so an exact value stays exact:
     * "90071992547409.93" becomes 9007199254740993 centavos, which a double
     * could not have represented.
     *
     * Accepts what the database and request layer actually hand us: an int, a
     * decimal string such as "10500.00", or a float.
     *
     * Floats are supported only for backward compatibility with the small values
     * SQLite and the existing model casts return, and only inside this
     * application's schema range — 0.00 to 99,999,999.99, the DECIMAL(10,2)
     * domain every persisted money column uses. Inside that range a double
     * resolves centavos unambiguously. Outside it, a double can no longer tell
     * adjacent centavos apart, so the value is refused rather than silently
     * rounded; an exact decimal string carries any larger amount losslessly.
     */
    public static function fromDecimal(int|float|string|null $amount): self
    {
        if ($amount === null || $amount === '') {
            return self::zero();
        }

        if (is_int($amount)) {
            return new self($amount * 100);
        }

        if (is_float($amount) && (! is_finite($amount) || $amount < 0.0 || $amount > self::MAXIMUM_FLOAT_AMOUNT)) {
            throw new InvalidArgumentException(
                'A monetary amount outside 0.00 to '.self::MAXIMUM_FLOAT_AMOUNT.' cannot be '
                .'trusted as a float, because a double can no longer distinguish adjacent '
                .'centavos there; pass it as an exact decimal string instead.'
            );
        }

        $normalized = is_float($amount)
            ? number_format($amount, 4, '.', '')
            : trim($amount);

        // A leading minus is matched only so it can be rejected with a clear
        // message rather than falling through to "unparseable".
        if (! preg_match('/^(-?)(\d*)(?:\.(\d*))?$/', $normalized, $matches)) {
            throw new InvalidArgumentException("Unparseable monetary amount [{$amount}].");
        }

        $whole = $matches[2] === '' ? '0' : $matches[2];
        $fraction = $matches[3] ?? '';

        $centavos = ((int) $whole) * 100 + (int) substr(str_pad($fraction, 2, '0'), 0, 2);

        // HALF_UP on the third decimal, carried out in integers.
        if (strlen($fraction) > 2 && (int) $fraction[2] >= 5) {
            $centavos++;
        }

        return new self($matches[1] === '-' ? -$centavos : $centavos);
    }

    public function centavos(): int
    {
        return $this->centavos;
    }

    public function plus(self $other): self
    {
        return new self($this->centavos + $other->centavos);
    }

    /**
     * @throws InvalidArgumentException when the result would be negative
     */
    public function minus(self $other): self
    {
        return new self($this->centavos - $other->centavos);
    }

    /**
     * Subtract, treating an overshoot as nothing left rather than a credit.
     *
     * This is what an outstanding balance needs: overpaying a booking leaves
     * nothing owed, not a negative amount owed.
     */
    public function minusOrZero(self $other): self
    {
        return new self(max(0, $this->centavos - $other->centavos));
    }

    public function multipliedBy(int $factor): self
    {
        return new self($this->centavos * $factor);
    }

    /**
     * Half the amount, rounded HALF_UP to the centavo.
     *
     * An odd centavo total therefore rounds the customer's half payment up, so
     * two halves never under-settle the booking.
     */
    public function half(): self
    {
        return new self(intdiv($this->centavos + 1, 2));
    }

    public function isZero(): bool
    {
        return $this->centavos === 0;
    }

    public function equals(self $other): bool
    {
        return $this->centavos === $other->centavos;
    }

    public function greaterThan(self $other): bool
    {
        return $this->centavos > $other->centavos;
    }

    public function lessThan(self $other): bool
    {
        return $this->centavos < $other->centavos;
    }

    /**
     * The canonical persisted representation, e.g. "10500.00".
     */
    public function toDecimalString(): string
    {
        return intdiv($this->centavos, 100).'.'.str_pad((string) ($this->centavos % 100), 2, '0', STR_PAD_LEFT);
    }

    /**
     * For display and JSON only, never as the authoritative value.
     */
    public function toFloat(): float
    {
        return $this->centavos / 100;
    }

    public function __toString(): string
    {
        return $this->toDecimalString();
    }
}
