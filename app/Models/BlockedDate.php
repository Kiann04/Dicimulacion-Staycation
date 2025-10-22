<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockedDate extends Model
{
    protected $table = 'blocked_dates';
    protected $fillable = ['date', 'reason'];
    public $timestamps = true;
}
