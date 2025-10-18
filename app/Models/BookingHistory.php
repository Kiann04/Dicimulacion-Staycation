<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BookingHistory extends Model
{
    use HasFactory;

    protected $table = 'booking_history';

    protected $fillable = [
        'booking_id',
        'user_id',
        'name',
        'staycation_id',
        'start_date',
        'end_date',
        'total_price',
        'payment_status',
        'deleted_at',
        'action_by',
    ];

    public $timestamps = false;
}
