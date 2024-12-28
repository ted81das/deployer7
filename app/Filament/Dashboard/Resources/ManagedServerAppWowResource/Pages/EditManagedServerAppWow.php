<?php

namespace App\Filament\Dashboard\Resources\ManagedServerAppWowResource\Pages;

use App\Filament\Dashboard\Resources\ManagedServerAppWowResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditManagedServerAppWow extends EditRecord
{
    protected static string $resource = ManagedServerAppWowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
