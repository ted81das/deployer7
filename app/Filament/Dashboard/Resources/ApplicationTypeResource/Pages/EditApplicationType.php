<?php

namespace App\Filament\Dashboard\Resources\ApplicationTypeResource\Pages;

use App\Filament\Dashboard\Resources\ApplicationTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditApplicationType extends EditRecord
{
    protected static string $resource = ApplicationTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
