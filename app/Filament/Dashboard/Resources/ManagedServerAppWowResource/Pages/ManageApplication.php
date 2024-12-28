<?php

namespace App\Filament\Dashboard\Resources\ManagedServerAppWowResource\Pages;

use App\Filament\Dashboard\Resources\ManagedServerAppWowResource;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Model;
use App\Models\ManagedServerAppWow; // Add this import
use Filament\Pages\Actions\Action;
use Illuminate\Support\Facades\Storage;
use App\Services\SSHConnectionService;

class ManageApplication extends Page
{
    protected static string $resource = ManagedServerAppWowResource::class;

    protected static string $view = 'filament.dashboard.resources.managed-server-app-wow-resource.pages.manage-application';
    
    
public $record;

    public function mount($record): void
    {
        $this->record = ManagedServerAppWow::find($record);

        if ($this->record->phpseclib_connection_status) {
            $this->record->updateConnectionStatus();
        }
    }

    protected function getActions(): array
    {
        return [
            Action::make('refresh-connection')
                ->label('Refresh Connection')
                ->action(fn () => $this->record->updateConnectionStatus())
                ->icon('heroicon-o-arrow-path')
                ->color('primary'),
        ];
    }

// Correct method where getTitle() is non-static
    public function getTitle(): string
    {
        return "Manage Application: {$this->record->application_name}";
    }

}
