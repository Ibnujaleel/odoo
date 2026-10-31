<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuotationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'plan_id',
        'validity_days',
    ];

    protected function casts(): array
    {
        return [
            'validity_days' => 'integer',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(QuotationTemplateLine::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
