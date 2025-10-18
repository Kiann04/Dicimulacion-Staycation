<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingHistory extends Model
{
    protected $table = 'booking_history'; // ✅ exact table name

    protected $fillable = [
        'booking_id',
        'user_id',
        'staycation_id',
        'status',
        'payment_status',
        'deleted_by',
        'deleted_at',
    ];

    public $timestamps = true; // since you have created_at / updated_at columns
}
