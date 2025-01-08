<?php

namespace App\Filament\Dashboard\Resources\ServerControlPanelResource\Pages;

use App\Filament\Dashboard\Resources\ServerControlPanelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

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
                
                // Add Delete Action
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Delete Server Control Panel')
                ->modalDescription('Are you sure you want to delete this server control panel? This action cannot be undone.')
                ->modalSubmitActionLabel('Yes, delete it')
                ->modalCancelActionLabel('No, cancel')
                ->before(function () {
                    // Validation before deletion
                    if ($this->record->servers()->count() > 0) {
                        Notification::make()
                            ->danger()
                            ->title('Cannot Delete')
                            ->body('This control panel has associated servers. Please remove all servers first.')
                            ->send();
                            
                        $this->halt();
                    }
                })
                ->after(function () {
                    Notification::make()
                        ->success()
                        ->title('Server Control Panel Deleted')
                        ->body('The server control panel has been successfully deleted.')
                        ->send();
                }),
        ];
    }
}
