<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaycationImage extends Model
{
    use HasFactory;

    protected $fillable = ['staycation_id', 'image_path'];

    public function staycation()
    {
        return $this->belongsTo(Staycation::class);
    }
}
