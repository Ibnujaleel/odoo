<?php

namespace App\Filament\Resources\SubscriptionResource\Pages;

use App\Enums\SubscriptionStatus;
use App\Filament\Resources\SubscriptionResource;
use App\Mail\QuotationMail;
use App\Models\Subscription;
use App\Services\QuotationShareService;
use App\Services\SubscriptionStateMachine;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;

class ViewSubscription extends ViewRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('printPdf')
                ->label('Print PDF')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn () => route('portal.quotations.pdf', $this->record))
                ->openUrlInNewTab(),

            Actions\Action::make('whatsappShare')
                ->label('Share on WhatsApp')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('success')
                ->url(fn () => QuotationShareService::whatsappUrl($this->record))
                ->openUrlInNewTab(),

            Actions\Action::make('emailQuotation')
                ->label('Email Quotation')
                ->icon('heroicon-o-envelope')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Email Quotation to Customer')
                ->modalDescription(fn () => "Send PDF quotation {$this->record->number} to {$this->record->customer?->email}?")
                ->action(function () {
                    if (empty($this->record->customer?->email)) {
                        Notification::make()->title('Error')->danger()->body('No email found for customer.')->send();

                        return;
                    }
                    try {
                        Mail::to($this->record->customer->email)->send(new QuotationMail($this->record));
                        Notification::make()->title('Email Sent')->success()->body("Quotation sent to {$this->record->customer->email}")->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Email Logged')->warning()->send();
                    }
                }),

            Actions\Action::make('sendQuotationWorkflow')
                ->label('Send Quote')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->visible(fn () => ($this->record->status instanceof SubscriptionStatus ? $this->record->status->value : (string) $this->record->status) === SubscriptionStatus::Draft->value)
                ->requiresConfirmation()
                ->modalHeading('Move to Quotation Status')
                ->modalDescription('Transition draft to quotation, making it available for customer review.')
                ->action(function () {
                    app(SubscriptionStateMachine::class)->transition($this->record, SubscriptionStatus::Quotation->value);
                    $this->refreshFormData(['status']);
                    Notification::make()->title('Moved to Quotation')->success()->send();
                }),

            Actions\Action::make('confirmWorkflow')
                ->label('Confirm')
                ->icon('heroicon-o-check-circle')
                ->color('primary')
                ->visible(fn () => ($this->record->status instanceof SubscriptionStatus ? $this->record->status->value : (string) $this->record->status) === SubscriptionStatus::Quotation->value)
                ->requiresConfirmation()
                ->modalHeading('Confirm Quotation')
                ->modalDescription('Confirm quotation terms before activation.')
                ->action(function () {
                    app(SubscriptionStateMachine::class)->transition($this->record, SubscriptionStatus::Confirmed->value);
                    $this->refreshFormData(['status']);
                    Notification::make()->title('Quotation Confirmed')->success()->send();
                }),

            Actions\Action::make('activateWorkflow')
                ->label('Activate')
                ->icon('heroicon-o-play')
                ->color('success')
                ->visible(fn () => ($this->record->status instanceof SubscriptionStatus ? $this->record->status->value : (string) $this->record->status) === SubscriptionStatus::Confirmed->value)
                ->requiresConfirmation()
                ->modalHeading('Activate Subscription')
                ->modalDescription('Activate subscription and initiate recurring billing cycle.')
                ->action(function () {
                    app(SubscriptionStateMachine::class)->transition($this->record, SubscriptionStatus::Active->value);
                    $this->refreshFormData(['status']);
                    Notification::make()->title('Subscription Activated')->success()->send();
                }),

            Actions\Action::make('closeWorkflow')
                ->label('Close')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => ($this->record->status instanceof SubscriptionStatus ? $this->record->status->value : (string) $this->record->status) === SubscriptionStatus::Active->value)
                ->requiresConfirmation()
                ->modalHeading('Close Subscription')
                ->modalDescription('Permanently close this active subscription?')
                ->action(function () {
                    app(SubscriptionStateMachine::class)->transition($this->record, SubscriptionStatus::Closed->value);
                    $this->refreshFormData(['status']);
                    Notification::make()->title('Subscription Closed')->danger()->send();
                }),

            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Subscription Status Timeline')
                ->schema([
                    Infolists\Components\TextEntry::make('timeline')
                        ->label('')
                        ->html()
                        ->state(function (Subscription $record): HtmlString {
                            $current = $record->status instanceof SubscriptionStatus ? $record->status->value : (string) $record->status;
                            $steps = [
                                'draft' => 'Draft',
                                'quotation' => 'Quotation Sent',
                                'confirmed' => 'Confirmed',
                                'active' => 'Active',
                                'closed' => 'Closed',
                            ];

                            $order = array_keys($steps);
                            $currentIndex = array_search($current, $order, true);
                            if ($currentIndex === false) {
                                $currentIndex = 0;
                            }

                            $html = '<div style="display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 12px 0;">';
                            foreach ($order as $idx => $key) {
                                $isPast = $idx < $currentIndex;
                                $isCurrent = $idx === $currentIndex;
                                $label = $steps[$key];

                                $bgColor = $isCurrent ? '#4f46e5' : ($isPast ? '#10b981' : '#e2e8f0');
                                $textColor = ($isCurrent || $isPast) ? '#ffffff' : '#64748b';
                                $labelColor = $isCurrent ? '#4f46e5' : ($isPast ? '#0f172a' : '#94a3b8');
                                $fontWeight = $isCurrent ? '700' : '500';
                                $symbol = $isPast ? '&#10003;' : (string) ($idx + 1);

                                $html .= '<div style="display: flex; flex-direction: column; align-items: center; position: relative; flex: 1;">';
                                $html .= "<div style=\"width: 32px; height: 32px; border-radius: 9999px; background-color: {$bgColor}; color: {$textColor}; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 13px; z-index: 2;\">{$symbol}</div>";
                                $html .= "<div style=\"margin-top: 8px; font-size: 12px; font-weight: {$fontWeight}; color: {$labelColor}; text-align: center;\">{$label}</div>";
                                $html .= '</div>';

                                if ($idx < count($order) - 1) {
                                    $lineColor = $idx < $currentIndex ? '#10b981' : '#e2e8f0';
                                    $html .= "<div style=\"height: 3px; background-color: {$lineColor}; flex: 1; margin: 0 -8px 24px -8px; z-index: 1;\"></div>";
                                }
                            }
                            $html .= '</div>';

                            return new HtmlString($html);
                        }),
                ]),
        ]);
    }
}
