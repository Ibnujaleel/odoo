<?php

namespace App\Filament\Resources\QuotationTemplateResource\Pages;

use App\Filament\Resources\QuotationTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewQuotationTemplate extends ViewRecord
{
    protected static string $resource = QuotationTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
