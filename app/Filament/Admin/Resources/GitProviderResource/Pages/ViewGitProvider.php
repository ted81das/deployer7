<?php

namespace App\Filament\Admin\Resources\GitProviderResource\Pages;

use App\Filament\Admin\Resources\GitProviderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewGitProvider extends ViewRecord
{
    protected static string $resource = GitProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
