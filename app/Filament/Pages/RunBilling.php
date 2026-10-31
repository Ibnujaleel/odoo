<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Jobs\GenerateDueInvoices;
use App\Services\BillingService;
use Filament\Actions\Action;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Admin-only page to manually trigger the billing job.
 *
 * Dispatches GenerateDueInvoices synchronously and reports how many
 * invoices were created and how many failed.
 */
class RunBilling extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Run Billing';

    protected static ?string $navigationLabel = 'Run Billing';

    protected static string $view = 'filament-panels::pages.page';

    public ?int $invoicesCreated = null;

    public ?int $failures = null;

    public ?string $lastRunAt = null;

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('runBilling')
                ->label('Run Billing Now')
                ->icon('heroicon-o-play')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Run billing for all due subscriptions?')
                ->modalDescription('This will generate invoices for all active subscriptions whose billing date is today or earlier. The process runs synchronously and may take a moment.')
                ->action(function () {
                    $job = new GenerateDueInvoices();
                    $job->handle(app(BillingService::class));

                    $this->invoicesCreated = $job->invoicesCreated;
                    $this->failures        = $job->failures;
                    $this->lastRunAt       = now()->format('Y-m-d H:i:s');

                    Notification::make()
                        ->title('Billing run complete')
                        ->body("{$this->invoicesCreated} invoice(s) created, {$this->failures} failure(s).")
                        ->success()
                        ->send();
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->state([
                'lastRunAt'       => $this->lastRunAt ?? 'Not run yet during this session',
                'invoicesCreated' => $this->invoicesCreated ?? 0,
                'failures'        => $this->failures ?? 0,
            ])
            ->schema([
                Section::make('Manual Billing Execution')
                    ->description('Manually trigger recurring billing for all active subscriptions whose next billing date is due. Click "Run Billing Now" in the header to execute.')
                    ->schema([
                        TextEntry::make('lastRunAt')
                            ->label('Last Execution Time'),
                        TextEntry::make('invoicesCreated')
                            ->label('Invoices Created')
                            ->badge()
                            ->color('success'),
                        TextEntry::make('failures')
                            ->label('Failed Subscriptions')
                            ->badge()
                            ->color(fn ($state) => $state > 0 ? 'danger' : 'gray'),
                    ])
                    ->columns(3),
            ]);
    }
}
