<?php

namespace App\Filament\Admin\Resources\GitProviderResource\Pages;

use App\Filament\Admin\Resources\GitProviderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGitProvider extends EditRecord
{
    protected static string $resource = GitProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
