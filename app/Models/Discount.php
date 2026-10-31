<?php

namespace App\Models;

use App\Enums\DiscountAppliesTo;
use App\Enums\DiscountType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'value',
        'min_purchase',
        'min_quantity',
        'start_date',
        'end_date',
        'usage_limit',
        'times_used',
        'applies_to',
        'product_id',
        'subscription_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => DiscountType::class,
            'applies_to' => DiscountAppliesTo::class,
            'value' => 'decimal:2',
            'min_purchase' => 'decimal:2',
            'min_quantity' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'usage_limit' => 'integer',
            'times_used' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(DiscountUsage::class);
    }

    public function invoices(): BelongsToMany
    {
        return $this->belongsToMany(Invoice::class, 'discount_usages')
            ->withTimestamps();
    }
}
