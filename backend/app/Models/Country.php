<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $fillable = [
        'iso_code_2',
        'iso_code_3',
        'name',
        'currency_id',
        'market_id',
    ];

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function market()
    {
        return $this->belongsTo(Market::class);
    }
}
