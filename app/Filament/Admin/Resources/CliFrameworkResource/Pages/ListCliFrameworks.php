<?php

namespace App\Filament\Admin\Resources\CliFrameworkResource\Pages;

use App\Filament\Admin\Resources\CliFrameworkResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCliFrameworks extends ListRecords
{
    protected static string $resource = CliFrameworkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
