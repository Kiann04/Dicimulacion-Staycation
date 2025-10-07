<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = ['user_id','booking_id','rating','comment'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function booking() 
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }
    public function staycation()
    {
    return $this->hasOneThrough(
        Staycation::class,   // final model
        Booking::class,      // intermediate model
        'id',                // Foreign key on bookings table
        'id',                // Foreign key on staycations table
        'booking_id',        // Local key on reviews table
        'staycation_id'      // Local key on bookings table
    );
    }

}
