<?php

namespace App\Models;

use App\Enums\TaxType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tax extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'rate',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'type' => TaxType::class,
            'rate' => 'decimal:4',
            'is_active' => 'boolean',
        ];
    }

    public function subscriptionLines(): HasMany
    {
        return $this->hasMany(SubscriptionLine::class);
    }
}
