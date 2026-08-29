<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use HasFactory;
    use SoftDeletes;

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
        'payment_status',
        'payment_method',
        'payment_proof',
        'transaction_number',
        'message_to_admin',
    ];

    /**
     * status and payment_status are deliberately left as plain strings rather than
     * cast to their enums: the Blade admin and customer screens compare and format
     * them as strings (ucfirst, ===, strtolower) and would break under enum casts.
     * Use bookingStatus() / paymentStatus() when the enum is wanted.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'end_date' => 'datetime',
            'total_price' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'price_per_day' => 'decimal:2',
        ];
    }

    /**
     * Payment proof paths point at a private disk and must never reach a client.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'payment_proof',
    ];

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

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function getFormattedStartDateAttribute(): string
    {
        return $this->start_date->timezone('Asia/Manila')->format('M d, Y');
    }

    public function getFormattedEndDateAttribute(): string
    {
        return $this->end_date->timezone('Asia/Manila')->format('M d, Y');
    }

    public function bookingStatus(): ?BookingStatus
    {
        return BookingStatus::tryFrom((string) $this->status);
    }

    public function paymentStatus(): ?PaymentStatus
    {
        return PaymentStatus::tryFrom(strtolower((string) $this->payment_status));
    }

    public function blocksAvailability(): bool
    {
        return $this->bookingStatus()?->blocksAvailability() ?? false;
    }

    /**
     * Only bookings in a blocking status reserve the calendar. Cancelled and
     * declined bookings remain queryable for history but free their dates.
     */
    public function scopeBlockingAvailability(Builder $query): Builder
    {
        return $query->whereIn('status', BookingStatus::blockingValues());
    }

    /**
     * Half-open date semantics: a stay occupies [start_date, end_date). One guest
     * checking out on the same day another checks in is not an overlap.
     *
     * The bounds are normalised to a full timestamp before comparison. Eloquent
     * writes these columns as "Y-m-d H:i:s", so comparing them against a bare
     * "Y-m-d" makes SQLite fall back to a lexicographic comparison in which
     * "2026-10-15 00:00:00" sorts after "2026-10-15" - which would reject a
     * perfectly valid back-to-back booking. Normalising keeps the comparison a
     * plain indexed column comparison on both MySQL and SQLite.
     */
    public function scopeOverlapping(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->where('start_date', '<', self::dateBoundary($endDate))
            ->where('end_date', '>', self::dateBoundary($startDate));
    }

    public static function dateBoundary(string $date): string
    {
        return CarbonImmutable::parse($date)->startOfDay()->format('Y-m-d H:i:s');
    }

    /** Sum of verified ledger payments, which is the authoritative amount received. */
    public function verifiedPaymentsTotal(): string
    {
        return (string) $this->payments()->verified()->sum('amount');
    }

    public function balanceDue(): string
    {
        return bcsub((string) $this->total_price, (string) ($this->amount_paid ?? '0'), 2);
    }
}
