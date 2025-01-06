<?php

namespace App\Filament\Dashboard\Resources\ServerResource\Pages;

use App\Filament\Dashboard\Resources\ServerResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewServer extends ViewRecord
{
    protected static string $resource = ServerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
