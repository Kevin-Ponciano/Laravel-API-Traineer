<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CoinCrypto extends Model
{
    protected $fillable = [
        'type',
        'acronym',
        'value_in_real',
    ];

    public function wallet(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }
}
