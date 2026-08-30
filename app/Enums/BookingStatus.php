<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Waiting = 'waiting';
    case Pending = 'pending';
    case Approved = 'approved';
    case Confirmed = 'confirmed';
    case Completed = 'completed';
    case Declined = 'declined';
    case Cancelled = 'cancelled';

    /**
     * Resolve a loosely cased / legacy value into a known status.
     */
    public static function fromLoose(?string $value): ?self
    {
        return self::tryFrom(strtolower(trim((string) $value)));
    }

    /**
     * The only statuses that release a date range back to the calendar.
     *
     * Availability is decided by excluding these rather than by listing the
     * statuses that hold dates: an unknown, malformed or null legacy status
     * must keep holding its inventory until a human has audited it, never be
     * silently treated as free.
     *
     * @return array<int, string>
     */
    public static function releasedValues(): array
    {
        return [
            self::Declined->value,
            self::Cancelled->value,
        ];
    }

    /**
     * Statuses that hold a date range and therefore make it unavailable.
     *
     * @return array<int, string>
     */
    public static function blockingValues(): array
    {
        return array_map(
            fn (self $status): string => $status->value,
            array_filter(self::cases(), fn (self $status): bool => $status->blocksAvailability())
        );
    }

    public function blocksAvailability(): bool
    {
        return ! in_array($this->value, self::releasedValues(), true);
    }

    /**
     * Whether a raw column value releases its dates.
     *
     * Anything that does not resolve to an explicitly released status — including
     * null and values written by older code — is treated as still holding.
     */
    public static function valueReleasesDates(?string $value): bool
    {
        return in_array(self::fromLoose($value)?->value, self::releasedValues(), true);
    }

    /**
     * A booking that has run its course and must not be reopened.
     *
     * Settlement, rescheduling and completion all refuse to act on these: none
     * of those operations is a reopen decision, and letting a payment or a date
     * change quietly revive a cancelled stay is how a released room gets sold
     * twice. Reopening, if it is ever wanted, needs its own deliberate flow.
     */
    public function isTerminal(): bool
    {
        return in_array($this, [self::Cancelled, self::Declined, self::Completed], true);
    }

    /**
     * A booking still moving through its normal lifecycle.
     */
    public function isActive(): bool
    {
        return ! $this->isTerminal();
    }

    /**
     * The statuses this one may move to.
     *
     * A cancelled booking is terminal, and a declined one cannot be quietly
     * approved again — reopening it is a decision that needs its own deliberate
     * flow rather than a side effect of the approve button.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Waiting, self::Pending => [self::Approved, self::Confirmed, self::Declined, self::Cancelled],
            self::Approved => [self::Confirmed, self::Declined, self::Cancelled, self::Completed],
            self::Confirmed => [self::Cancelled, self::Completed],
            self::Completed => [],
            self::Declined => [self::Cancelled],
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
}
