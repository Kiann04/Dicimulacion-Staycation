<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockedDate extends Model
{
    use HasFactory;

    protected $fillable = [
        'staycation_id',
        'start_date',
        'end_date',
        'reason',
    ];

    public $timestamps = true;
}
