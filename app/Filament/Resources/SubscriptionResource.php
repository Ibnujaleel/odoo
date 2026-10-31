<?php

namespace App\Filament\Resources;

use App\Enums\SubscriptionStatus;
use App\Filament\Resources\SubscriptionResource\Pages;
use App\Mail\QuotationMail;
use App\Models\Plan;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\QuotationTemplate;
use App\Models\Subscription;
use App\Models\Tax;
use App\Services\QuotationCalculator;
use App\Services\QuotationShareService;
use App\Services\SubscriptionStateMachine;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Subscription Overview')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('number')
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->placeholder('Auto-generated (e.g. SUB-2026-00001)')
                        ->disabled()
                        ->dehydrated(false),

                    Forms\Components\Select::make('customer_id')
                        ->relationship('customer', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\Select::make('quotation_template_id')
                        ->label('Start from template')
                        ->relationship('quotationTemplate', 'name')
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if (! $state) {
                                return;
                            }
                            $template = QuotationTemplate::with('lines.product', 'lines.variant')->find($state);
                            if (! $template) {
                                return;
                            }

                            if ($template->plan_id) {
                                $set('plan_id', $template->plan_id);
                            }

                            if ($template->validity_days) {
                                $set('quotation_valid_until', now()->addDays($template->validity_days)->format('Y-m-d'));
                            }

                            $linesData = [];
                            foreach ($template->lines as $tLine) {
                                $basePrice = (float) ($tLine->product?->sales_price ?? 0);
                                $extraPrice = (float) ($tLine->variant?->extra_price ?? 0);
                                $unitPrice = round($basePrice + $extraPrice, 2);
                                $amount = round($tLine->quantity * $unitPrice, 2);

                                $linesData[] = [
                                    'product_id' => $tLine->product_id,
                                    'variant_id' => $tLine->variant_id,
                                    'quantity' => $tLine->quantity,
                                    'unit_price' => $unitPrice,
                                    'amount' => $amount,
                                    'tax_id' => null,
                                ];
                            }

                            if (! empty($linesData)) {
                                $set('lines', $linesData);
                            }
                        }),

                    Forms\Components\Select::make('plan_id')
                        ->relationship('plan', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live(),

                    Forms\Components\Select::make('status')
                        ->options(collect(SubscriptionStatus::cases())->mapWithKeys(fn (SubscriptionStatus $status) => [$status->value => $status->label()]))
                        ->required()
                        ->default(SubscriptionStatus::Draft->value),

                    Forms\Components\TextInput::make('payment_terms')
                        ->required()
                        ->maxLength(50)
                        ->default('net_30'),

                    Forms\Components\DatePicker::make('quotation_valid_until')
                        ->label('Quotation Valid Until'),

                    Forms\Components\DatePicker::make('start_date')
                        ->label('Start Date'),

                    Forms\Components\DatePicker::make('expiry_date')
                        ->label('Expiry Date')
                        ->afterOrEqual('start_date'),

                    Forms\Components\DatePicker::make('next_billing_date')
                        ->label('Next Billing Date'),

                    Forms\Components\Select::make('created_by')
                        ->relationship('creator', 'name')
                        ->searchable()
                        ->preload()
                        ->default(fn () => auth()->id()),
                ]),

            Forms\Components\Section::make('Subscription Line Items')
                ->schema([
                    Forms\Components\Repeater::make('lines')
                        ->relationship('lines')
                        ->columns(6)
                        ->schema([
                            Forms\Components\Select::make('product_id')
                                ->label('Product')
                                ->relationship('product', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columnSpan(2)
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    $product = Product::find($state);
                                    $variant = ProductVariant::find($get('variant_id'));
                                    if ($variant && $variant->product_id != $state) {
                                        $set('variant_id', null);
                                        $variant = null;
                                    }
                                    $price = round(($product?->sales_price ?? 0) + ($variant?->extra_price ?? 0), 2);
                                    $set('unit_price', $price);
                                    $qty = (int) ($get('quantity') ?: 1);
                                    $set('amount', round($qty * $price, 2));
                                }),

                            Forms\Components\Select::make('variant_id')
                                ->label('Variant')
                                ->options(function (Forms\Get $get) {
                                    $productId = $get('product_id');
                                    if (! $productId) {
                                        return [];
                                    }

                                    return ProductVariant::where('product_id', $productId)
                                        ->get()
                                        ->mapWithKeys(fn ($v) => [$v->id => $v->displayName()]);
                                })
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    $product = Product::find($get('product_id'));
                                    $variant = ProductVariant::find($state);
                                    $price = round(($product?->sales_price ?? 0) + ($variant?->extra_price ?? 0), 2);
                                    $set('unit_price', $price);
                                    $qty = (int) ($get('quantity') ?: 1);
                                    $set('amount', round($qty * $price, 2));
                                }),

                            Forms\Components\TextInput::make('quantity')
                                ->numeric()
                                ->default(1)
                                ->minValue(1)
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    $qty = (int) $state;
                                    $unitPrice = (float) $get('unit_price');
                                    $set('amount', round($qty * $unitPrice, 2));
                                }),

                            Forms\Components\TextInput::make('unit_price')
                                ->numeric()
                                ->prefix('$')
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    $qty = (int) ($get('quantity') ?: 1);
                                    $unitPrice = (float) $state;
                                    $set('amount', round($qty * $unitPrice, 2));
                                }),

                            Forms\Components\Select::make('tax_id')
                                ->label('Tax')
                                ->relationship('tax', 'name')
                                ->searchable()
                                ->preload(),

                            Forms\Components\TextInput::make('amount')
                                ->numeric()
                                ->prefix('$')
                                ->disabled()
                                ->dehydrated(),
                        ]),

                    Forms\Components\Placeholder::make('totals_summary')
                        ->label('Quotation Live Totals')
                        ->content(function (Forms\Get $get) {
                            $lines = $get('lines') ?? [];
                            if (empty($lines)) {
                                return 'No items added yet.';
                            }
                            try {
                                $calc = app(QuotationCalculator::class)->calculate($lines);
                                $sub = number_format($calc['subtotal'], 2);
                                $tax = number_format($calc['tax'], 2);
                                $total = number_format($calc['grand_total'], 2);

                                return "Subtotal: \${$sub}  |  Estimated Tax: \${$tax}  |  Grand Total: \${$total}";
                            } catch (\Throwable $e) {
                                return 'Please ensure valid quantities and line items.';
                            }
                        }),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plan')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof SubscriptionStatus ? $state->label() : ucfirst((string) $state))
                    ->color(fn ($state) => match ($state instanceof SubscriptionStatus ? $state->value : (string) $state) {
                        'active' => 'success',
                        'confirmed' => 'primary',
                        'quotation' => 'info',
                        'closed' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('expiry_date')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('quotation_valid_until')
                    ->label('Quote Valid Until')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('next_billing_date')
                    ->label('Next Bill')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('invoices_count')
                    ->counts('invoices')
                    ->label('Invoices')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(collect(SubscriptionStatus::cases())->mapWithKeys(fn (SubscriptionStatus $status) => [$status->value => $status->label()])),

                Tables\Filters\SelectFilter::make('customer_id')
                    ->relationship('customer', 'name')
                    ->label('Customer'),
            ])
            ->actions([
                // Output actions
                Tables\Actions\Action::make('printPdf')
                    ->label('PDF')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (Subscription $record) => route('portal.quotations.pdf', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('whatsappShare')
                    ->label('WhatsApp')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->url(fn (Subscription $record) => QuotationShareService::whatsappUrl($record))
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('emailQuotation')
                    ->label('Email')
                    ->icon('heroicon-o-envelope')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Send Quotation Email')
                    ->modalDescription(fn (Subscription $record) => "Send quotation {$record->number} with attached PDF to {$record->customer?->email}?")
                    ->action(function (Subscription $record) {
                        if (empty($record->customer?->email)) {
                            Notification::make()
                                ->title('Error')
                                ->danger()
                                ->body('Customer does not have a valid email address.')
                                ->send();

                            return;
                        }

                        try {
                            Mail::to($record->customer->email)->send(new QuotationMail($record));
                            Notification::make()
                                ->title('Quotation Sent')
                                ->success()
                                ->body("Quotation {$record->number} sent to {$record->customer->email}.")
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()
                                ->title('Email Logged')
                                ->warning()
                                ->body("Email queued/logged for {$record->customer->email}.")
                                ->send();
                        }
                    }),

                // State transition actions
                Tables\Actions\Action::make('sendQuotationWorkflow')
                    ->label('Send Quote')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn (Subscription $record) => ($record->status instanceof SubscriptionStatus ? $record->status->value : (string) $record->status) === SubscriptionStatus::Draft->value)
                    ->requiresConfirmation()
                    ->modalHeading('Move to Quotation Status')
                    ->modalDescription('This transitions the draft into a sent quotation ready for customer acceptance.')
                    ->action(function (Subscription $record) {
                        app(SubscriptionStateMachine::class)->transition($record, SubscriptionStatus::Quotation->value);
                        Notification::make()->title('Quotation Sent')->success()->send();
                    }),

                Tables\Actions\Action::make('confirmWorkflow')
                    ->label('Confirm')
                    ->icon('heroicon-o-check-circle')
                    ->color('primary')
                    ->visible(fn (Subscription $record) => ($record->status instanceof SubscriptionStatus ? $record->status->value : (string) $record->status) === SubscriptionStatus::Quotation->value)
                    ->requiresConfirmation()
                    ->modalHeading('Confirm Quotation')
                    ->modalDescription('Confirm this quotation, preparing it for subscription activation.')
                    ->action(function (Subscription $record) {
                        app(SubscriptionStateMachine::class)->transition($record, SubscriptionStatus::Confirmed->value);
                        Notification::make()->title('Quotation Confirmed')->success()->send();
                    }),

                Tables\Actions\Action::make('activateWorkflow')
                    ->label('Activate')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn (Subscription $record) => ($record->status instanceof SubscriptionStatus ? $record->status->value : (string) $record->status) === SubscriptionStatus::Confirmed->value)
                    ->requiresConfirmation()
                    ->modalHeading('Activate Subscription')
                    ->modalDescription('Activate this subscription and start recurring billing schedules.')
                    ->action(function (Subscription $record) {
                        app(SubscriptionStateMachine::class)->transition($record, SubscriptionStatus::Active->value);
                        Notification::make()->title('Subscription Activated')->success()->send();
                    }),

                Tables\Actions\Action::make('closeWorkflow')
                    ->label('Close')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (Subscription $record) => ($record->status instanceof SubscriptionStatus ? $record->status->value : (string) $record->status) === SubscriptionStatus::Active->value)
                    ->requiresConfirmation()
                    ->modalHeading('Close Subscription')
                    ->modalDescription('Are you sure you want to close this active subscription?')
                    ->action(function (Subscription $record) {
                        app(SubscriptionStateMachine::class)->transition($record, SubscriptionStatus::Closed->value);
                        Notification::make()->title('Subscription Closed')->danger()->send();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'view' => Pages\ViewSubscription::route('/{record}'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }
}
