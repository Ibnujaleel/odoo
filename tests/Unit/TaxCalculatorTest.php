<?php

declare(strict_types=1);

use App\Models\Tax;
use App\Services\TaxCalculator;

beforeEach(function () {
    $this->calculator = new TaxCalculator();
});

/*
|--------------------------------------------------------------------------
| Null Tax
|--------------------------------------------------------------------------
*/

it('returns zero when tax is null', function () {
    expect($this->calculator->calculate(100.00, null))->toBe(0.0);
});

it('returns zero for zero net with null tax', function () {
    expect($this->calculator->calculate(0.0, null))->toBe(0.0);
});

/*
|--------------------------------------------------------------------------
| Percentage Tax
|--------------------------------------------------------------------------
*/

it('calculates percentage tax correctly', function () {
    $tax = new Tax([
        'name'      => 'GST',
        'type'      => 'percentage',
        'rate'      => 18,
        'is_active' => true,
    ]);

    expect($this->calculator->calculate(1000.00, $tax))->toBe(180.0);
});

it('rounds percentage tax to two decimals', function () {
    $tax = new Tax([
        'name'      => 'VAT',
        'type'      => 'percentage',
        'rate'      => 7,
        'is_active' => true,
    ]);

    // 33.33 × 7 / 100 = 2.3331 → 2.33
    expect($this->calculator->calculate(33.33, $tax))->toBe(2.33);
});

it('calculates percentage tax on small amounts', function () {
    $tax = new Tax([
        'name'      => 'Micro Tax',
        'type'      => 'percentage',
        'rate'      => 5,
        'is_active' => true,
    ]);

    // 0.50 × 5 / 100 = 0.025 → 0.03 (round half up)
    expect($this->calculator->calculate(0.50, $tax))->toBe(0.03);
});

it('calculates percentage tax at 100 percent', function () {
    $tax = new Tax([
        'name'      => 'Full Tax',
        'type'      => 'percentage',
        'rate'      => 100,
        'is_active' => true,
    ]);

    expect($this->calculator->calculate(250.00, $tax))->toBe(250.0);
});

/*
|--------------------------------------------------------------------------
| Fixed Tax
|--------------------------------------------------------------------------
*/

it('returns fixed rate regardless of net amount', function () {
    $tax = new Tax([
        'name'      => 'Service Fee',
        'type'      => 'fixed',
        'rate'      => 25,
        'is_active' => true,
    ]);

    expect($this->calculator->calculate(1000.00, $tax))->toBe(25.0);
    expect($this->calculator->calculate(1.00, $tax))->toBe(25.0);
    expect($this->calculator->calculate(0.0, $tax))->toBe(25.0);
});

it('rounds fixed tax to two decimals', function () {
    $tax = new Tax([
        'name'      => 'Odd Fee',
        'type'      => 'fixed',
        'rate'      => 9.999,
        'is_active' => true,
    ]);

    expect($this->calculator->calculate(100.00, $tax))->toBe(10.0);
});

/*
|--------------------------------------------------------------------------
| Inactive Tax
|--------------------------------------------------------------------------
*/

it('returns zero for an inactive tax', function () {
    $tax = new Tax([
        'name'      => 'Old Tax',
        'type'      => 'percentage',
        'rate'      => 18,
        'is_active' => false,
    ]);

    expect($this->calculator->calculate(500.00, $tax))->toBe(0.0);
});

/*
|--------------------------------------------------------------------------
| Edge Cases
|--------------------------------------------------------------------------
*/

it('returns zero for zero net with percentage tax', function () {
    $tax = new Tax([
        'name'      => 'GST',
        'type'      => 'percentage',
        'rate'      => 18,
        'is_active' => true,
    ]);

    expect($this->calculator->calculate(0.0, $tax))->toBe(0.0);
});

it('handles a zero-rate percentage tax', function () {
    $tax = new Tax([
        'name'      => 'Exempt',
        'type'      => 'percentage',
        'rate'      => 0,
        'is_active' => true,
    ]);

    expect($this->calculator->calculate(500.00, $tax))->toBe(0.0);
});

it('handles a zero-rate fixed tax', function () {
    $tax = new Tax([
        'name'      => 'Free',
        'type'      => 'fixed',
        'rate'      => 0,
        'is_active' => true,
    ]);

    expect($this->calculator->calculate(500.00, $tax))->toBe(0.0);
});
