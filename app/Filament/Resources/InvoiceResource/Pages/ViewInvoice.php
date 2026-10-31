<?php

namespace App\Filament\Resources\InvoiceResource\Pages;

use App\Filament\Resources\InvoiceResource;
use App\Services\InvoiceService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('printPdf')
                ->label('Print PDF')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn () => route('portal.invoices.pdf', $this->record))
                ->openUrlInNewTab(),

            Actions\Action::make('confirm')
                ->label('Confirm')
                ->icon('heroicon-o-check-circle')
                ->color('primary')
                ->visible(fn (): bool => ($this->record->status->value ?? (string) $this->record->status) === 'draft')
                ->requiresConfirmation()
                ->action(function () {
                    try {
                        app(InvoiceService::class)->confirm($this->record);
                        $this->refreshFormData(['status']);
                        Notification::make()->title('Invoice Confirmed')->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
                    }
                }),

            Actions\Action::make('send')
                ->label('Send')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->requiresConfirmation()
                ->action(function () {
                    try {
                        app(InvoiceService::class)->send($this->record);
                        $this->refreshFormData(['sent_at']);
                        Notification::make()->title('Invoice Sent')->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Error')->body($e->getMessage())->danger()->send();
                    }
                }),

            Actions\EditAction::make(),
        ];
    }
}
