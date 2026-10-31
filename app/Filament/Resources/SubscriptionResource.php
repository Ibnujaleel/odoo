<?php

namespace App\Filament\Resources;

use App\Enums\SubscriptionStatus;
use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('number')
                ->maxLength(255)
                ->unique(ignoreRecord: true)
                ->placeholder('Generated automatically'),
            Forms\Components\Select::make('customer_id')
                ->relationship('customer', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('plan_id')
                ->relationship('plan', 'name')
                ->searchable()
                ->preload()
                ->required(),
            Forms\Components\Select::make('quotation_template_id')
                ->relationship('quotationTemplate', 'name')
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('status')
                ->options(collect(SubscriptionStatus::cases())->mapWithKeys(fn (SubscriptionStatus $status) => [$status->value => $status->label()]))
                ->required()
                ->default(SubscriptionStatus::Draft->value),
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\DatePicker::make('start_date'),
                Forms\Components\DatePicker::make('expiry_date')->afterOrEqual('start_date'),
                Forms\Components\DatePicker::make('next_billing_date'),
                Forms\Components\DatePicker::make('quotation_valid_until'),
                Forms\Components\TextInput::make('payment_terms')
                    ->required()
                    ->maxLength(50)
                    ->default('net_30'),
                Forms\Components\Select::make('created_by')
                    ->relationship('creator', 'name')
                    ->searchable()
                    ->preload(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('number')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('customer.name')->label('Customer')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('plan.name')->label('Plan')->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof SubscriptionStatus ? $state->label() : ucfirst((string) $state))
                    ->color(fn ($state) => match ($state instanceof SubscriptionStatus ? $state->value : (string) $state) {
                        'active' => 'success',
                        'quotation' => 'info',
                        'confirmed' => 'primary',
                        'closed' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('start_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('next_billing_date')->label('Next bill')->date()->sortable(),
                Tables\Columns\TextColumn::make('invoices_count')->counts('invoices')->label('Invoices')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50])
            ->actions([
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
