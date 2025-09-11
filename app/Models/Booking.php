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
        'user_id', // <-- add this so it can be mass-assigned
        'name',
        'phone',
        'status',
        'guest_number',
        'start_date',
        'end_date',
        'price_per_day',
        'total_price'
    ];

    public function staycation()
    {
        return $this->belongsTo(Staycation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class); // Link to user table
    }

    public static function boot()
    {
        parent::boot();

        static::saving(function ($booking) {
            $days = Carbon::parse($booking->start_date)
                        ->diffInDays(Carbon::parse($booking->end_date)) + 1; // include last day
            $booking->total_price = $days * $booking->price_per_day;
        });
    }
    public function review()
    {
        return $this->hasOne(Review::class);
    }

}
