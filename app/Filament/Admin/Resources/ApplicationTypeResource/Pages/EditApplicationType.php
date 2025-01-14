<?php

namespace App\Filament\Admin\Resources\ApplicationTypeResource\Pages;

use App\Filament\Admin\Resources\ApplicationTypeResource;
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
