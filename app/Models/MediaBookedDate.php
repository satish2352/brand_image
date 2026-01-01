<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MediaBookedDate extends Model
{
    use HasFactory;

    protected $table = 'media_booked_date';

    protected $fillable = [
        'media_id',
        'from_date',
        'to_date',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date'   => 'date',
    ];

    public $timestamps = true;
}
