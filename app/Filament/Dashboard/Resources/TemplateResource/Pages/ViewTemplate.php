<?php

namespace App\Filament\Dashboard\Resources\TemplateResource\Pages;

use App\Filament\Dashboard\Resources\TemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTemplate extends ViewRecord
{
    protected static string $resource = TemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
