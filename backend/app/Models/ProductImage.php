<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'url',
        'alt_text',
        'is_primary',
        'display_order',
        'width',
        'height',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'display_order' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
