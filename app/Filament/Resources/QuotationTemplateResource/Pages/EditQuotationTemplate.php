<?php

declare(strict_types=1);

namespace App\Filament\Resources\QuotationTemplateResource\Pages;

use App\Filament\Resources\QuotationTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQuotationTemplate extends EditRecord
{
    protected static string $resource = QuotationTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => auth()->user()?->hasRole('admin')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

