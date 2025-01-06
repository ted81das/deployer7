<?php

namespace App\Filament\Dashboard\Resources\ServerControlPanelResource\Pages;

use App\Filament\Dashboard\Resources\ServerControlPanelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditServerControlPanel extends EditRecord
{
    protected static string $resource = ServerControlPanelResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return static::getResource()::mutateFormDataBeforeSave($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('authenticate')
                ->visible(fn () => $this->record->authentication_status !== 'authenticated')
                ->action(fn () => $this->record->authenticate()),
        ];
    }
}
