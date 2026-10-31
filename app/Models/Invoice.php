<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'subscription_id',
        'customer_id',
        'billing_period_start',
        'billing_period_end',
        'status',
        'subtotal',
        'discount_total',
        'tax_total',
        'total',
        'amount_paid',
        'due_date',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'billing_period_start' => 'date',
            'billing_period_end' => 'date',
            'due_date' => 'date',
            'sent_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Invoice $invoice): void {
            if (blank($invoice->number)) {
                $invoice->number = 'INV-'.now()->format('Y').'-'.str_pad((string) (static::max('id') + 1), 5, '0', STR_PAD_LEFT);
            }
        });
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function discounts(): BelongsToMany
    {
        return $this->belongsToMany(Discount::class, 'discount_usages')
            ->withTimestamps();
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('status', InvoiceStatus::Confirmed->value)
            ->whereDate('due_date', '<', today())
            ->whereColumn('total', '>', 'amount_paid');
    }

    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereIn('status', [InvoiceStatus::Draft->value, InvoiceStatus::Confirmed->value])
            ->whereColumn('total', '>', 'amount_paid');
    }

    public function balance(): float
    {
        return max(0, (float) $this->total - (float) $this->amount_paid);
    }

    public function isOverdue(): bool
    {
        return $this->status === InvoiceStatus::Confirmed
            && $this->due_date !== null
            && $this->due_date->isPast()
            && $this->balance() > 0;
    }

    public function displayStatus(): string
    {
        return $this->isOverdue() ? 'overdue' : $this->status->value;
    }
}
