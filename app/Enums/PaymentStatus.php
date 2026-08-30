<?php

namespace App\Enums;

use App\Support\Money;

/**
 * Where a booking stands financially.
 *
 * `Pending` means the customer has submitted a payment proof that nobody has
 * checked yet. `Unpaid`, `HalfPaid` and `Paid` are all statements an
 * administrator has verified; `amount_paid` only ever reflects verified money.
 */
enum PaymentStatus: string
{
    case Unpaid = 'unpaid';
    case Pending = 'pending';
    case HalfPaid = 'half_paid';
    case Paid = 'paid';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    /**
     * Resolve a loosely cased / legacy value (e.g. the historic "Pending"
     * column default, or "Half Paid") into a known status.
     */
    public static function fromLoose(?string $value): ?self
    {
        $normalized = str_replace([' ', '-'], '_', strtolower(trim((string) $value)));

        return self::tryFrom($normalized);
    }

    /**
     * Statuses an administrator may set through the payment endpoints.
     *
     * @return array<int, string>
     */
    public static function adminAssignableValues(): array
    {
        return [
            self::Unpaid->value,
            self::Pending->value,
            self::HalfPaid->value,
            self::Paid->value,
        ];
    }

    /**
     * Whether this status asserts that money has actually been received.
     */
    public function isVerified(): bool
    {
        return in_array($this, [self::HalfPaid, self::Paid], true);
    }

    /**
     * The statuses this one may move to.
     *
     * Verified money is never silently unwound: neither `Paid` nor `HalfPaid`
     * can drop back to `Unpaid` or `Pending`, and `Failed` cannot jump straight
     * to a settled state. Reversals belong to a refund flow that does not exist
     * yet, so they are refused rather than approximated.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Unpaid, self::HalfPaid, self::Paid, self::Failed, self::Cancelled],
            self::Unpaid => [self::Pending, self::HalfPaid, self::Paid, self::Failed, self::Cancelled],
            self::HalfPaid => [self::Paid, self::Cancelled],
            self::Paid => [self::Cancelled],
            self::Failed => [self::Pending, self::Unpaid, self::Cancelled],
            self::Cancelled => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        if ($this === $target) {
            return true;
        }

        return in_array($target, $this->allowedTransitions(), true);
    }

    /**
     * The verified amount this status implies for a given booking total.
     *
     * Half payments round HALF_UP to the centavo, so two halves never settle
     * for less than the total.
     */
    public function verifiedAmountFor(Money $total): Money
    {
        return match ($this) {
            self::Paid => $total,
            self::HalfPaid => $total->half(),
            default => Money::zero(),
        };
    }
}
