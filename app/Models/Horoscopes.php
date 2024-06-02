<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Horoscopes extends Model
{
    protected $table = 'horoscopes';
    protected $fillable = [
        'signo',
        'message_basic',
        'work_basic',
        'message_premium',
        'lucky_premium',
        'love_premium',
        'health_premium',
        'start_date',
        'end_date',
    ];
}
