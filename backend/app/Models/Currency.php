<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'rate_to_usd',
        'decimals',
        'is_active',
    ];

    protected $casts = [
        'rate_to_usd' => 'float',
        'decimals' => 'integer',
        'is_active' => 'boolean',
    ];

    public function markets()
    {
        return $this->hasMany(Market::class, 'default_currency_id');
    }

    public function countries()
    {
        return $this->hasMany(Country::class);
    }
}
