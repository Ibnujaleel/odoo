<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Mail\CustomerInvitationMail;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('company')
                ->maxLength(255),
            Forms\Components\TextInput::make('email')
                ->email()
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('phone')
                ->tel()
                ->maxLength(50)
                ->regex('/^([0-9\s\-\+\(\)]*)$/')
                ->helperText('International or local phone format with digits, dashes, and + prefix.'),
            Forms\Components\Textarea::make('address')
                ->rows(3)
                ->columnSpanFull(),
            Forms\Components\Select::make('user_id')
                ->label('Portal user')
                ->relationship('user', 'email')
                ->searchable()
                ->preload()
                ->helperText('Linked user account granting customer portal access.'),
            Forms\Components\Select::make('assigned_to')
                ->label('Assigned staff')
                ->relationship('assignedTo', 'name', fn ($query) => $query->whereHas('roles', fn ($q) => $q->whereIn('name', ['admin', 'staff'])))
                ->searchable()
                ->preload(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('company')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Assigned to')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('subscriptions_count')
                    ->counts('subscriptions')
                    ->label('Subscriptions')
                    ->sortable(),
                Tables\Columns\TextColumn::make('invite_token')
                    ->label('Invitation')
                    ->badge()
                    ->formatStateUsing(fn ($state, $record) => $record->user_id ? 'Activated' : ($state ? 'Pending' : 'Not Invited'))
                    ->color(fn ($state, $record) => $record->user_id ? 'success' : ($state ? 'warning' : 'gray'))
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->paginated([10, 25, 50])
            ->filters([
                Tables\Filters\SelectFilter::make('assigned_to')
                    ->relationship('assignedTo', 'name')
                    ->label('Assigned Staff'),
            ])
            ->actions([
                Tables\Actions\Action::make('sendInvitation')
                    ->label('Send invitation')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Send Portal Invitation')
                    ->modalDescription(fn (Customer $record) => "Generate an invitation token for {$record->name} and send an activation email.")
                    ->action(function (Customer $record) {
                        $token = Str::random(40);
                        $record->update(['invite_token' => $token]);

                        try {
                            Mail::to($record->email)->send(new CustomerInvitationMail($record));
                        } catch (\Throwable $e) {
                            // Demo fallback: keep going even if mail transport isn't configured
                        }

                        $activationUrl = url('/activate/'.$token);

                        Notification::make()
                            ->title('Invitation Sent')
                            ->success()
                            ->body("Activation link: {$activationUrl}")
                            ->actions([
                                NotificationAction::make('openActivation')
                                    ->label('Open Link')
                                    ->url($activationUrl, shouldOpenInNewTab: true),
                            ])
                            ->persistent()
                            ->send();
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
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
