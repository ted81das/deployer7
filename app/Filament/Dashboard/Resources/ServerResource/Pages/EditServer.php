<?php

namespace App\Filament\Dashboard\Resources\ServerResource\Pages;

use App\Filament\Dashboard\Resources\ServerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServer extends EditRecord
{
    protected static string $resource = ServerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
