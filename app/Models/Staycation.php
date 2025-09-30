<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staycation extends Model
{
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    // Reviews through bookings
    public function reviews()
    {
        return $this->hasManyThrough(
            Review::class,
            Booking::class,
            'staycation_id', // Foreign key on bookings
            'booking_id',    // Foreign key on reviews
            'id',            // Local key on staycations
            'id'             // Local key on bookings
        );
    }
}

