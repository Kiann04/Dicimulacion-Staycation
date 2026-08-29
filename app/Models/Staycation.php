<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

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

    /**
     * house_price is cast so it serialises identically on MySQL and SQLite.
     * Without it the API returns "3000" on one driver and "3000.00" on the other.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'house_price' => 'decimal:2',
        ];
    }

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

    public function bookingHistories()
    {
        return $this->hasMany(BookingHistory::class);
    }

    public function blockedDates()
    {
        return $this->hasMany(BlockedDate::class);
    }

    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(Payment::class, Booking::class);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('house_availability', 'available');
    }

    public function isBookable(): bool
    {
        return $this->house_availability === 'available';
    }
}
