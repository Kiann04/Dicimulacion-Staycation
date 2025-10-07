<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'booking_id',
        'rating',
        'comment',
    ];

    // The user who wrote the review
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // The booking this review belongs to
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
