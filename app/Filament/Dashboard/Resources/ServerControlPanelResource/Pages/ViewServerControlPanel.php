<?php

namespace App\Filament\Dashboard\Resources\ServerControlPanelResource\Pages;

use App\Filament\Dashboard\Resources\ServerControlPanelResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewServerControlPanel extends ViewRecord
{
    protected static string $resource = ServerControlPanelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
