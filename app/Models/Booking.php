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
        'phone',
        'status',
        'guest_number',
        'start_date',
        'end_date',
        'total_price'
    ];

    public function staycation()
    {
        return $this->belongsTo(Staycation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($booking) {
            if ($booking->staycation) {
                $days = Carbon::parse($booking->start_date)
                            ->diffInDays(Carbon::parse($booking->end_date)) + 1; // include last day
                $booking->total_price = $days * $booking->staycation->house_price;
            }
        });
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
