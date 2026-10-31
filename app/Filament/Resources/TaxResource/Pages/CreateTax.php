<?php

declare(strict_types=1);

namespace App\Filament\Resources\TaxResource\Pages;

use App\Filament\Resources\TaxResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTax extends CreateRecord
{
    protected static string $resource = TaxResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

