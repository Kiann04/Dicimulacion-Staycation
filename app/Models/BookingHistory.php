<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A booking that has been permanently removed, kept for audit and refunds.
 *
 * `booking_id` deliberately has no foreign key: the booking it names is gone by
 * design. `user_id` is retained so the customer who made it can still be
 * identified — which is what lets them read back their own payment proof.
 */
class BookingHistory extends Model
{
    use HasFactory;

    protected $table = 'booking_history';

    protected $fillable = [
        'booking_id',
        'user_id',
        'name',
        'staycation_id',
        'start_date',
        'end_date',
        'total_price',
        'payment_status',
        'payment_proof',
        'deleted_at',
        'action_by',
        'action_at',
    ];

    public $timestamps = false;

    public function staycation(): BelongsTo
    {
        return $this->belongsTo(Staycation::class, 'staycation_id', 'id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
