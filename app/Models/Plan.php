<?php

namespace App\Models;

use App\Enums\BillingPeriod;
use App\Enums\PlanStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'billing_period',
        'min_quantity',
        'start_date',
        'end_date',
        'auto_close',
        'closable',
        'pausable',
        'renewable',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'billing_period' => BillingPeriod::class,
            'status' => PlanStatus::class,
            'min_quantity' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'auto_close' => 'boolean',
            'closable' => 'boolean',
            'pausable' => 'boolean',
            'renewable' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function quotationTemplates(): HasMany
    {
        return $this->hasMany(QuotationTemplate::class);
    }
}
