<?php

declare(strict_types=1);

namespace App\Filament\Resources\QuotationTemplateResource\Pages;

use App\Filament\Resources\QuotationTemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuotationTemplate extends CreateRecord
{
    protected static string $resource = QuotationTemplateResource::class;
}
