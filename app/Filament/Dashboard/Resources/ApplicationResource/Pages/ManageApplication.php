<?php

namespace App\Filament\Dashboard\Resources\ApplicationResource\Pages;

use App\Filament\Dashboard\Resources\ApplicationResource;
use Filament\Resources\Pages\Page;
use App\Models\Application; // Add this import
use Filament\Pages\Actions\Action;
use App\Services\SSHConnectionService;

class ManageApplication extends Page
{
    protected static string $resource = ApplicationResource::class;

    protected static string $view = 'filament.dashboard.resources.application-resource.pages.manage-application';

//protected static string $view = 'filament.resources.application.pages.manage-application';
    
    public $record;
    public $cli_framework;
    public $manage_sections;

    public function mount($record): void
    {
        $this->record = Application::find($record);
        
        // Initialize CLI framework
        $this->cli_framework = $this->record->cli_framework ?? 'wordpress';
        
        // Set manage sections based on CLI framework
        $this->manage_sections = $this->getManageSections();
    }

    private function getManageSections(): array
    {
        if ($this->cli_framework === 'wordpress') {
            return ['plugins', 'optimize', 'security'];
        }
        
        return ['customize', 'optimize', 'model', 'generate_resource'];
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

    public function getTitle(): string
    {
        return "Manage Application: {$this->record->name}";
    }
    
    

}