<?php

declare(strict_types=1);

namespace App\Filament\Resources\QuotationTemplateResource\Pages;

use App\Filament\Resources\QuotationTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuotationTemplates extends ListRecords
{
    protected static string $resource = QuotationTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->visible(fn () => auth()->user()?->hasRole('admin')),
        ];
    }
}

