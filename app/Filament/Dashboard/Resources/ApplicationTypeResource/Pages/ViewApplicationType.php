<?php

namespace App\Filament\Dashboard\Resources\ApplicationTypeResource\Pages;

use App\Filament\Dashboard\Resources\ApplicationTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewApplicationType extends ViewRecord
{
    protected static string $resource = ApplicationTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
