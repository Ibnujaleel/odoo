<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class AdminRevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Revenue collected (last 6 months)';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $start = now()->startOfMonth()->subMonths(5);

        $monthExpression = DB::connection()->getDriverName() === 'pgsql'
            ? "to_char(paid_on, 'YYYY-MM')"
            : "strftime('%Y-%m', paid_on)";

        $rows = Payment::query()
            ->where('paid_on', '>=', $start->toDateString())
            ->selectRaw("{$monthExpression} as month, SUM(amount) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        $labels = [];
        $values = [];

        for ($i = 0; $i < 6; $i++) {
            $month = $start->copy()->addMonths($i);
            $labels[] = $month->format('M Y');
            $values[] = round((float) ($rows[$month->format('Y-m')] ?? 0), 2);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $values,
                    'borderColor' => '#4f46e5',
                    'backgroundColor' => 'rgba(79, 70, 229, 0.12)',
                    'fill' => true,
                    'tension' => 0.35,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
