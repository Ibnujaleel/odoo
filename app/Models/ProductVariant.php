<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'attribute',
        'value',
        'extra_price',
    ];

    protected function casts(): array
    {
        return [
            'extra_price' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function quotationTemplateLines(): HasMany
    {
        return $this->hasMany(QuotationTemplateLine::class, 'variant_id');
    }

    public function subscriptionLines(): HasMany
    {
        return $this->hasMany(SubscriptionLine::class, 'variant_id');
    }

    public function displayName(): string
    {
        return ucfirst($this->attribute).': '.$this->value;
    }
}
