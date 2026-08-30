<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\Expression;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'staycation_id',
        'user_id',
        'name',
        'email',
        'phone',
        'status',
        'guest_number',
        'start_date',
        'end_date',
        'price_per_day',
        'total_price',
        'amount_paid',
        'declared_amount',
        'payment_type',
        'payment_status',
        'payment_method',
        'payment_proof',
        'transaction_number',
        'message_to_admin',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'total_price' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'declared_amount' => 'decimal:2',
            'guest_number' => 'integer',
        ];
    }

    public function staycation(): BelongsTo
    {
        return $this->belongsTo(Staycation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function history(): HasOne
    {
        return $this->hasOne(BookingHistory::class, 'booking_id');
    }

    /**
     * Bookings that still hold their dates against the availability calendar.
     *
     * Written as an exclusion so that a null or unrecognised legacy status keeps
     * holding its inventory instead of silently freeing it.
     *
     * The comparison is deliberately byte-exact. This schema's columns are
     * utf8mb4_unicode_ci, which is both case-insensitive and PAD SPACE, so a
     * plain `status NOT IN ('declined', 'cancelled')` would let 'DECLINED' or
     * 'declined ' compare equal and quietly release a room that is still
     * occupied. Only the canonical lowercase values may release inventory.
     */
    public function scopeHoldingDates(Builder $query): Builder
    {
        $status = self::byteExactStatusColumn($query);

        return $query->where(function (Builder $query) use ($status): void {
            $query->whereNull('status')
                ->orWhereNotIn($status, BookingStatus::releasedValues());
        });
    }

    /**
     * A `status` reference that compares byte-for-byte on the current driver.
     *
     * SQLite's default TEXT collation is already binary, so the bare column is
     * correct there. MySQL and MariaDB need an explicit cast, which only affects
     * this residual filter: availability queries are driven by the
     * (staycation_id, start_date, end_date) index, not by status.
     *
     * @param  Builder<Booking>  $query
     */
    public static function byteExactStatusColumn(Builder $query): Expression|string
    {
        $driver = $query->getConnection()->getDriverName();

        return in_array($driver, ['mysql', 'mariadb'], true)
            ? new Expression('CAST(`status` AS BINARY)')
            : 'status';
    }

    public function bookingStatus(): ?BookingStatus
    {
        return BookingStatus::fromLoose($this->status);
    }

    public function paymentStatus(): ?PaymentStatus
    {
        return PaymentStatus::fromLoose($this->payment_status);
    }

    public function totalPrice(): Money
    {
        return Money::fromDecimal($this->total_price);
    }

    public function amountPaid(): Money
    {
        return Money::fromDecimal($this->amount_paid);
    }

    public function declaredAmount(): Money
    {
        return Money::fromDecimal($this->declared_amount);
    }

    /**
     * What is still owed, computed in centavos rather than floats.
     */
    public function remainingBalance(): Money
    {
        return $this->totalPrice()->minusOrZero($this->amountPaid());
    }

    public function getRemainingBalanceAttribute(): float
    {
        return $this->remainingBalance()->toFloat();
    }

    public function getFormattedStartDateAttribute(): string
    {
        return $this->start_date->timezone('Asia/Manila')->format('M d, Y');
    }

    public function getFormattedEndDateAttribute(): string
    {
        return $this->end_date->timezone('Asia/Manila')->format('M d, Y');
    }
}
