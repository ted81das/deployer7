<?php

namespace App\Filament\Admin\Resources\CliFrameworkResource\Pages;

use App\Filament\Admin\Resources\CliFrameworkResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCliFramework extends EditRecord
{
    protected static string $resource = CliFrameworkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
