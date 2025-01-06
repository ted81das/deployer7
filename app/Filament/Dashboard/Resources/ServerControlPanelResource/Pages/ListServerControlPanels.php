<?php

namespace App\Filament\Dashboard\Resources\ServerControlPanelResource\Pages;

use App\Filament\Dashboard\Resources\ServerControlPanelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServerControlPanels extends ListRecords
{
    protected static string $resource = ServerControlPanelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
