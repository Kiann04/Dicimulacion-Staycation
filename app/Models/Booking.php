<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Booking extends Model
{
    use HasFactory;

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
        'message_to_admin'
    ];

    public function staycation()
    {
        return $this->belongsTo(Staycation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviews()
    {
        return $this->hasOne(Review::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($booking) {
            if ($booking->staycation) {
                $nights = Carbon::parse($booking->start_date)
                    ->diffInDays(Carbon::parse($booking->end_date));
                $booking->total_price = $nights * $booking->staycation->house_price;
            }
        });
    }
}
