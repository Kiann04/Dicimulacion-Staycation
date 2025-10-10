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
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function staycation()
    {
        return $this->belongsTo(Staycation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }
    public function getFormattedStartDateAttribute()
    {
        return $this->start_date->timezone('Asia/Manila')->format('M d, Y h:i A');
    }

    public function getFormattedEndDateAttribute()
    {
        return $this->end_date->timezone('Asia/Manila')->format('M d, Y h:i A');
    }
}
