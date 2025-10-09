<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staycation extends Model
{
    use HasFactory;

    // ✅ Allow mass assignment for these columns
    protected $fillable = [
        'house_name',
        'house_description',
        'house_price',
        'house_location',
        'house_availability',
        'house_image', // keep this for single-image uploads (still needed even if you add multiple)
    ];

    // ✅ Relationship: a staycation can have many bookings
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    // ✅ Relationship: a staycation can have many reviews through bookings
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

    // ✅ Relationship: a staycation can have multiple images (optional, for carousel)
    public function images()
    {
        return $this->hasMany(StaycationImage::class);
    }
}
