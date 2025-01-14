<?php

namespace App\Filament\Admin\Resources\GitProviderResource\Pages;

use App\Filament\Admin\Resources\GitProviderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGitProviders extends ListRecords
{
    protected static string $resource = GitProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
