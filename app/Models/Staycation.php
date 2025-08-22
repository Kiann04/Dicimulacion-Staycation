<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staycation extends Model
{
    use HasFactory;

    protected $fillable = [
        'house_name',
        'house_description',
        'house_price',
        'house_image',
        'house_location',
        'house_availability',
    ];
}
