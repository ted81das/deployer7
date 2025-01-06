<?php

namespace App\Filament\Dashboard\Resources\ServerResource\Pages;

use App\Filament\Dashboard\Resources\ServerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListServers extends ListRecords
{
    protected static string $resource = ServerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
