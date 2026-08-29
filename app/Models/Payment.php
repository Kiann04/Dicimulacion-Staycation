<?php

namespace App\Models;

use App\Enums\PaymentRecordStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'amount',
        'type',
        'payment_method',
        'reference_number',
        'proof_path',
        'status',
        'verified_by',
        'verified_at',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'verified_at' => 'datetime',
        ];
    }

    /**
     * The proof path points at a private disk and must never be serialised to a client.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'proof_path',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('status', PaymentRecordStatus::Verified->value);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', PaymentRecordStatus::Pending->value);
    }

    public function isVerified(): bool
    {
        return $this->status === PaymentRecordStatus::Verified->value;
    }
}
