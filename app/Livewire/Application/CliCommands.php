<?php
// app/Livewire/Application/CliCommands.php

namespace App\Livewire\Application;

use Livewire\Component;
use App\Models\Application;
use App\Services\SSHConnectionService;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Crypt;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class CliCommands extends Component implements HasForms
{
  
  use InteractsWithForms;
  
    public Application $record;
    public array $manage_sections = []; // Add this property
    public string $selectedSection = '';
    public string $selectedCommand = '';
    public string $commandOutput = '';
    public array $availableCommands = [];

    protected $commands = [
        'wordpress' => [
            'plugins' => [
                'list_plugins' => [
                    'label' => 'List plugins',
                    'command' => 'wp plugin list'
                ],
                'install_woocommerce' => [
                    'label' => 'Install and Activate Ecommerce',
                    'command' => 'wp plugin install --activate woocommerce'
                ],
                'activate_woocommerce' => [
                    'label' => 'Activate ecommerce',
                    'command' => 'wp plugin activate woocommerce'
                ]
            ],
            'security' => [
                'install_security' => [
                    'label' => 'Install and Activate security plugin',
                    'command' => 'wp plugin install --activate sg-security'
                ],
                'enable_xmlrpc' => [
                    'label' => 'Enable XML-RPC',
                    'command' => 'wp sg secure xml-rpc enable'
                ],
                'disable_xmlrpc' => [
                    'label' => 'Disable XML-RPC',
                    'command' => 'wp sg secure xml-rpc disable'
                ]
            ]
        ],
        'laravel' => [
            'model' => [
                'list_models' => [
                    'label' => 'List Models',
                    'command' => 'php artisan model:list'
                ]
            ],
            'optimize' => [
                'cache_clear' => [
                    'label' => 'Clear Cache',
                    'command' => 'php artisan cache:clear'
                ]
            ]
        ]
        // Add other frameworks here
    ];

    public function mount(Application $record): void
    {
        $this->record = $record;
      //   $this->manage_sections = $manage_sections; // Set the manage_sections
         $this->manage_sections = $this->getManageSections();
        $this->updateAvailableCommands();
    }


private function getManageSections(): array
{
    if ($this->record->cli_framework === 'wordpress') {
        return ['plugins', 'optimize', 'security'];
    }
    
    //HARD CODED WORDPRESS SECTIONS - NEEDS TO CHANGE
     return ['plugins', 'optimize', 'security'];
  //  return ['customize', 'optimize', 'model', 'generate_resource'];
}


    public function updatedSelectedSection(): void
    {
        $this->selectedCommand = '';
        $this->updateAvailableCommands();
    }

/*    private function updateAvailableCommands(): void
    {
        if (empty($this->selectedSection)) {
            $this->availableCommands = [];
            return;
        }

        $framework = $this->record->cli_framework ?? 'wordpress';
        $this->availableCommands = collect($this->commands[$framework][$this->selectedSection] ?? [])
            ->pluck('label', 'command')
            ->toArray();
    }
    
    */
    
    
    private function updateAvailableCommands(): void
    {
        $this->availableCommands = $this->getCommandsForSection($this->selectedSection);
    }

    private function getCommandsForSection(string $section): array
    {
        if ($this->record->cli_framework === 'wordpress' || $this->record->cli_framework !== 'wordpress') {
            return match ($section) {
                'plugins' => [
                    'wp plugin list' => 'List all plugins',
                    'wp plugin install' => 'Install a plugin',
                    'wp plugin activate' => 'Activate a plugin',
                    'wp plugin deactivate' => 'Deactivate a plugin',
                ],
                'optimize' => [
                    'wp cache flush' => 'Flush cache',
                    'wp transient delete --all' => 'Delete all transients',
                    'wp optimize' => 'Optimize database',
                ],
                'security' => [
                    'wp security check' => 'Security check',
                    'wp core verify-checksums' => 'Verify core checksums',
                    'wp config list' => 'List configuration',
                ],
                default => [],
            };
        }

     /*  
     // Laravel commands
        return match ($section) {
            'customize' => [
                'php artisan config:clear' => 'Clear config cache',
                'php artisan cache:clear' => 'Clear application cache',
                'php artisan view:clear' => 'Clear compiled views',
            ],
            'optimize' => [
                'php artisan optimize' => 'Optimize application',
                'php artisan route:cache' => 'Cache routes',
                'php artisan config:cache' => 'Cache config',
            ],
            'model' => [
                'php artisan make:model' => 'Create new model',
                'php artisan make:migration' => 'Create new migration',
                'php artisan migrate' => 'Run migrations',
            ],
            'generate_resource' => [
                'php artisan make:controller' => 'Create new controller',
                'php artisan make:resource' => 'Create new resource',
                'php artisan make:seeder' => 'Create new seeder',
            ],
            default => [],
        };*/
    }


    public function executeCommand(): void
    {
        try {
            if (empty($this->selectedCommand)) {
                throw new \Exception('Please select a command.');
            }

            $sshService = app(SSHConnectionService::class);
            
            // Execute command
            $this->commandOutput = $sshService->executeCommand(
                $this->record,
                $this->selectedCommand
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

    public function render()
    {
        return view('livewire.application.cli-commands');
    }
}