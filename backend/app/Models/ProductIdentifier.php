<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductIdentifier extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'variant_id',
        'type',
        'value',
        'normalized_value',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /**
     * Normalize identifier value (uppercase, remove hyphens, spaces, leading zeros where applicable)
     */
    public static function normalize(string $type, string $value): string
    {
        $cleaned = strtoupper(trim(preg_replace('/[^a-zA-Z0-9]/', '', $value)));
        return $cleaned;
    }
}
