<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockedDate extends Model
{
    use HasFactory;

    protected $fillable = [
        'staycation_id',
        'start_date',
        'end_date',
        'reason',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function staycation(): BelongsTo
    {
        return $this->belongsTo(Staycation::class);
    }

    /**
     * A blocked range covers whole nights [start_date, end_date), matching the
     * half-open semantics used by bookings so adjacency stays bookable.
     * See Booking::dateBoundary() for why the bounds are normalised.
     */
    public function scopeOverlapping(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->where('start_date', '<', Booking::dateBoundary($endDate))
            ->where('end_date', '>', Booking::dateBoundary($startDate));
    }
}
