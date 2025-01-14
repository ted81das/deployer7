<?php

namespace App\Filament\Admin\Resources\CliFrameworkResource\Pages;

use App\Filament\Admin\Resources\CliFrameworkResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCliFramework extends ViewRecord
{
    protected static string $resource = CliFrameworkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
