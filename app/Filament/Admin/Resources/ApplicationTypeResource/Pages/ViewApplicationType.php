<?php

namespace App\Filament\Admin\Resources\ApplicationTypeResource\Pages;

use App\Filament\Admin\Resources\ApplicationTypeResource;
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
