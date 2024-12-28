<?php

namespace App\Livewire\ManagedServerAppWow;

use Livewire\Component;
use App\Models\ManagedServerAppWow;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use App\Services\SSHConnectionService;
use Illuminate\Support\Facades\Crypt;

class WpCliCommands extends Component implements HasForms
{
    use InteractsWithForms;

    public ?ManagedServerAppWow $record = null;
    public $selectedCommand = '';
    public $commandOutput = '';
    public $formState = [];

    protected $availableCommands = [
        'generate-salts' => [
            'id' => 'generate-salts',
            'label' => 'Generate Security Keys',
            'command' => 'wp config shuffle-salts',
            'params' => []
        ],
        'optimize-database' => [
            'id' => 'optimize-database',
            'label' => 'Optimize Database',
            'command' => 'wp db optimize',
            'params' => []
        ],
        'repair-database' => [
            'id' => 'repair-database',
            'label' => 'Repair Database',
            'command' => 'wp db repair',
            'params' => []
        ],
        // Add other commands...
    ];

    public function mount(ManagedServerAppWow $record): void
    {
        $this->record = $record;
        $this->form->fill();
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('command')
                ->label('Select WP-CLI Command')
                ->options(collect($this->availableCommands)->pluck('label', 'id'))
                ->placeholder('Choose a command')
                ->required()
                ->reactive()
        ];
    }

    protected function getFormStatePath(): string
    {
        return 'formState';
    }

/*
    public function executeCommand(): void
    {
        $data = $this->form->getState();

        try {
            if (empty($data['command'])) {
                throw new \Exception('Please select a command.');
            }

            $selectedCommand = $this->availableCommands[$data['command']] ?? null;
            
            if (!$selectedCommand) {
                throw new \Exception('Invalid command selected.');
            }

            $commandString = $selectedCommand['command'];

            // Execute command via SSH
            $sshService = app(SSHConnectionService::class);
            $appPath = "/home/{$this->record->application_user}/{$this->record->application_name}/public_html";

            $this->commandOutput = $sshService->executeCommand(
                $this->record,
                "cd {$appPath} && {$commandString}"
            );

            Notification::make()
                ->title('Command Executed Successfully')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Command Failed')
                ->danger()
                ->body($e->getMessage())
                ->send();

            $this->commandOutput = "Error: " . $e->getMessage();
        }
    }
*/

public function executeCommand(): void
{
    $data = $this->form->getState();

    try {
        if (empty($data['command'])) {
            throw new \Exception('Please select a command.');
        }

        $selectedCommand = $this->availableCommands[$data['command']] ?? null;
        
        if (!$selectedCommand) {
            throw new \Exception('Invalid command selected.');
        }

        $commandString = $selectedCommand['command'];

        // Execute command via SSH
        $sshService = app(SSHConnectionService::class);
        
        try {
            // Test connection first using credentials from database
            $privateKey = Crypt::decryptString($this->record->application_sshkey_private);
            
            if (!$sshService->testConnection(
                $this->record->app_hostname,
                $this->record->application_user,
                $privateKey
            )) {
                throw new \Exception('Unable to establish SSH connection.');
            }

            $this->commandOutput = $sshService->executeCommand(
                $this->record,
                $commandString
            );

            Notification::make()
                ->title('Command Executed Successfully')
                ->success()
                ->send();

        } catch (\Exception $e) {
            throw new \Exception('SSH Error: ' . $e->getMessage());
        }

    } catch (\Exception $e) {
        Notification::make()
            ->title('Command Failed')
            ->danger()
            ->body($e->getMessage())
            ->send();

        $this->commandOutput = "Error: " . $e->getMessage();
    }
}

    public function render()
    {
        return view('livewire.managed-server-app-wow.wp-cli-commands');
    }
}
