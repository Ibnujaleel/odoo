<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'customer_id',
        'plan_id',
        'quotation_template_id',
        'status',
        'start_date',
        'expiry_date',
        'payment_terms',
        'next_billing_date',
        'quotation_valid_until',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'start_date' => 'date',
            'expiry_date' => 'date',
            'next_billing_date' => 'date',
            'quotation_valid_until' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Subscription $subscription): void {
            if (blank($subscription->number)) {
                $subscription->number = 'SUB-'.now()->format('Y').'-'.str_pad((string) (static::max('id') + 1), 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function quotationTemplate(): BelongsTo
    {
        return $this->belongsTo(QuotationTemplate::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(SubscriptionLine::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasManyThrough
    {
        return $this->hasManyThrough(Payment::class, Invoice::class);
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(Discount::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', SubscriptionStatus::Active->value);
    }

    public function scopeDueForBilling(Builder $query): Builder
    {
        return $query->active()
            ->whereNotNull('next_billing_date')
            ->whereDate('next_billing_date', '<=', today());
    }

    public function subtotal(): float
    {
        return (float) $this->lines->sum('amount');
    }
}
