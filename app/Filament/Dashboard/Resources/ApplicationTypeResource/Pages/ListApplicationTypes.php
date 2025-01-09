<?php

namespace App\Filament\Dashboard\Resources\ApplicationTypeResource\Pages;

use App\Filament\Dashboard\Resources\ApplicationTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListApplicationTypes extends ListRecords
{
    protected static string $resource = ApplicationTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
