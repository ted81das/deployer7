<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\ServerResource\Pages;
use App\Filament\Dashboard\Resources\ServerResource\RelationManagers;
use App\Models\Server;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Services\ControlPanel\ControlPanelServiceFactory;
use Illuminate\Validation\Rules\Unique;
use Filament\Notifications\Notification;
use App\Services\SSHConnectionService;
use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;
use Illuminate\Support\Facades\Crypt;

class ServerResource extends Resource
{
    protected static ?string $model = Server::class;
    protected static ?string $navigationIcon = 'heroicon-o-server';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Section::make('Server Details')
                        ->schema([
                            Forms\Components\Select::make('server_control_panel_id')
                                ->relationship('controlPanel', 'name', function (Builder $query) {
                                    return $query->where('authentication_status', 'authenticated')
                                        ->where('user_id', auth()->id());
                                })
                                ->required()
                                ->reactive()
                                  ->disabled(fn ($livewire) => $livewire instanceof Pages\EditServer)
                                ->afterStateUpdated(fn ($state, callable $set) => 
                                    static::updateProviderOptions($state, $set)),

        Forms\Components\TextInput::make('name')
    ->required()
    ->maxLength(12)
    ->alphaNum()
      ->disabled(fn ($livewire) => $livewire instanceof Pages\EditServer)
    ->unique(),

                            Forms\Components\TextInput::make('hostname')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\Select::make('mapped_region')
                                ->options([
                                    'us-east' => 'US - East',
                                    'us-west' => 'US - West',
                                    'us-central' => 'US - Central',
                                    'eu-central' => 'EU - Central',
                                    'eu-west' => 'EU - West',
                                    'apac-east' => 'APAC - East',
                                    'apac-middle' => 'APAC - Middle',
                                    'apac-southeast' => 'APAC - Southeast'
                                ])
                                ->required(),

                            Forms\Components\Select::make('mapped_plan')
                                ->options([
                                    'starter' => 'Starter',
                                    'advanced' => 'Advanced',
                                    'premium' => 'Premium'
                                ])
                                ->required(),
                                
                Forms\Components\Select::make('provider_id')
    ->options(function (callable $get) {
        $controlPanelId = $get('server_control_panel_id');
        if (!$controlPanelId) {
            return [];
        }
        
        $controlPanel = \App\Models\ServerControlPanel::find($controlPanelId);
        if (!$controlPanel || empty($controlPanel->available_providers)) {
            return [];
        }

        // Convert available_providers array to options array
        // Here we ensure the key (provider_id) is preserved exactly as it is
        return collect($controlPanel->available_providers)
            ->mapWithKeys(function ($name, $providerId) {
                // Use the exact provider ID as both the key and value
                return [(int)$providerId => "{$providerId}: {$name}"];
            })
            ->toArray();
    })
    ->visible(function (callable $get) {
        $controlPanelId = $get('server_control_panel_id');
        if (!$controlPanelId) {
            return false;
        }

        $controlPanel = \App\Models\ServerControlPanel::find($controlPanelId);
        return $controlPanel && !empty($controlPanel->available_providers);
    })
    ->required()
    ->reactive() // Make sure this is here
      ->disabled(fn ($livewire) => $livewire instanceof Pages\EditServer)
    ->afterStateUpdated(function ($state, callable $set) {
        // Get the control panel and extract provider name
        $controlPanelId = request()->get('server_control_panel_id');
        if ($controlPanelId && $state) {
            $controlPanel = \App\Models\ServerControlPanel::find($controlPanelId);
            if ($controlPanel && !empty($controlPanel->available_providers)) {
                $providers = $controlPanel->available_providers;
                $providerName = $providers[$state] ?? null;
                if ($providerName) {
                    $set('provider', strtolower($providerName));
                }
            }
        }
        $set('region', null);
        $set('plan', null);
}),  
    
    // Add this separate field for displaying the provider name
Forms\Components\Select::make('provider')
    ->options(function (callable $get) {
        $providerId = $get('provider_id');
        $controlPanelId = $get('server_control_panel_id');
        
        if (!$providerId || !$controlPanelId) {
            return [];
        }

        $controlPanel = \App\Models\ServerControlPanel::find($controlPanelId);
        if (!$controlPanel || empty($controlPanel->available_providers)) {
            return [];
        }

        // Filter available_providers to only show the one matching provider_id
        return collect($controlPanel->available_providers)
            ->filter(function ($name, $id) use ($providerId) {
                return (int)$id === (int)$providerId;
            })
            ->toArray();
    })
    ->visible(function (callable $get) {
        return !empty($get('provider_id'));
    })
    ->required()
      ->disabled(fn ($livewire) => $livewire instanceof Pages\EditServer)
      ->reactive(),
    
    
    Forms\Components\Select::make('region')
                            ->options(function (callable $get) {
                                $controlPanelId = $get('server_control_panel_id');
                                $providerId = $get('provider_id');
                                
                                if (!$controlPanelId || !$providerId) {
                                    return [];
                                }

                                $controlPanel = \App\Models\ServerControlPanel::find($controlPanelId);
                                //dd($controlPanel);
                                if (!$controlPanel) {
                                  //dd('here no control panel');
                                    return ['ERROR - NO CONTROL PANEL RETURNED EXCEPTION'];
                                }

                                try {
                                    $credentials = $controlPanel->getCredentials();
                                    $apiToken = unserialize($credentials['api_token']);
                                   
                                    $service = app(ControlPanelServiceFactory::class)->create($controlPanel->type, $apiToken);
                                    //dd($service->getControlPanelRegions($providerId));
                                    return $service->getControlPanelRegions($providerId);
                                } catch (\Exception $e) {
                                    //dd('here with exception');
                                    return ['ERROR - NO REGION RETURNED EXCEPTION'];
                                }
                            })
                            ->visible(function (callable $get) {
                                return !empty($get('provider_id'));
                            })
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set) => $set('plan', null))
                              ->disabled(fn ($livewire) => $livewire instanceof Pages\EditServer)
                            ->required(),

                        Forms\Components\Select::make('plan')
                            ->options(function (callable $get) {
                                $controlPanelId = $get('server_control_panel_id');
                                $providerId = $get('provider_id');
                                $region = $get('region');
                                
                                if (!$controlPanelId || !$providerId || !$region) {
                                    return ['Not Available - Provider Id or Region'];
                                }

                                $controlPanel = \App\Models\ServerControlPanel::find($controlPanelId);
                                if (!$controlPanel) {
                                    return ['Not Available - ControlPanel'];
                                }

                                //try {
                                    $credentials = $controlPanel->getCredentials();
                                    $apiToken = unserialize($credentials['api_token']);
                                    $service = app(ControlPanelServiceFactory::class)->create($controlPanel->type, $apiToken);
                                    
                                    return $service->getControlPanelPlans($providerId, $region);
                                /*} catch (\Exception $e) {
                                     return [];
                                } */
                            })
                            ->visible(function (callable $get) {
                                return !empty($get('region'));
                            })
                              ->disabled(fn ($livewire) => $livewire instanceof Pages\EditServer)
                            ->required(),
    
          Forms\Components\Select::make('web_server')
                            ->options([
                                'nginx' => 'Nginx',
                                'apache2' => 'Apache',
                                'openlitespeed' => 'LiteSpeed',
                                'mern' => 'MERN'
                            ])
                            ->default('apache2')
                           ->required(),

                        Forms\Components\Select::make('database_type')
                            ->options([
                                'mariadb' => 'MariaDB',
                                'mysql' => 'MySQL'
                            ])
                            ->default('mysql')
                             ->disabled(fn ($livewire) => $livewire instanceof Pages\EditServer)
                            ->required(),

                           /* Forms\Components\Select::make('provider_id')
                                ->relationship('provider', 'name')
                                ->visible(fn (callable $get) => filled($get('server_control_panel_id')))
                                ->required(),*/
                        ])
                        ->columns(2),

                    // Edit-only section
                    Forms\Components\Section::make('Server Configuration')
                        ->schema([
                            Forms\Components\TextInput::make('php_version')
                                ->visible(fn ($livewire) => $livewire instanceof Pages\EditServer)
                                ->required(),

                            Forms\Components\TagsInput::make('php_disabled_functions')
                                ->visible(fn ($livewire) => $livewire instanceof Pages\EditServer)
                                ->separator(','),
                        ])
                        ->columns(2)
                        ->visible(fn ($livewire) => $livewire instanceof Pages\EditServer),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('hostname')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('controlPanel.name')
                    ->label('Panel'),
                Tables\Columns\BadgeColumn::make('provisioning_status')
                ->label('Available')
                    ->colors([
                        'danger' => 'failed',
                        'warning' => ['pending', 'provisioning'],
                        'success' => 'active',
                    ]),
                Tables\Columns\BadgeColumn::make('server_status')
                    ->label('Connection')
                    ->colors([
                        'danger' => 'failed',
                        'warning' => 'pending',
                        'success' => 'connected',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('check_status')
                    ->label(fn (Server $record) => match($record->provisioning_status) {
                    'pending' => 'Check Status (Pending)',
                    'failed' => 'Check Status (Failed)',
                    'provisioning' => 'Check Status (Provisioning)',
                    default => 'Check Status'
                })
                ->icon('heroicon-o-arrow-path')
                ->visible(fn (Server $record) => in_array($record->provisioning_status, [
                    'pending',
                    'failed',
                    'provisioning'
                ]))
                ->action(function (Server $record) {
                    try {
                        // Get the control panel service
                        $serviceFactory = app(ControlPanelServiceFactory::class);
                        $controlPanel = $record->controlPanel;
                        $credentials = $controlPanel->getCredentials();
                        $apiToken = unserialize($credentials['api_token']);
                        
                        // Create service instance
                        $service = $serviceFactory->create($controlPanel->type, $apiToken);
                        
                        // Get server details from API
                        $serverDetails = $service->showServerStatus($record->controlpanel_server_id);
                        
                        // Check SSH status and update server accordingly
                        if ($serverDetails['ssh_status'] === '1') {
                            $record->update([
                                'provisioning_status' => 'active',
                                //'server_status' => 'active',
                                'server_ip' => $serverDetails['ip']
                            ]);
                            
                            Notification::make()
                                ->success()
                                ->title('Server '.$record->controlpanel_server_id.' is now active. Status '.$serverDetails['ssh_status'])
                                ->send();
                        } else {
                            Notification::make()
                                ->warning()
                                ->title('Server '.$record->controlpanel_server_id.' is still provisioning. Status '.$serverDetails['ssh_status'])
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Failed to check server status')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
                    // Add Connect action
            Tables\Actions\Action::make('connect')
                ->label('Connect')
                ->icon('heroicon-o-link')
                ->visible(fn (Server $record): bool => 
                    $record->provisioning_status === 'active' && 
                    $record->server_status !== 'connected'
                )
                ->action(function (Server $record) {
                   // try {
                        $sshService = app(SSHConnectionService::class);
                        
                        // Get decrypted private key from database
                      $privateKey = Crypt::decryptString($record->server_sshkey_private);
                      //   $privateKey = $record->server_sshkey_private;
                       $privateKeyMod = str_replace('\n', "\n", unserialize($privateKey));
                       // Attempt SSH connection
                        $ssh = new SSH2($record->server_ip);
                       //  dd($privateKey);
                        $key = PublicKeyLoader::load($privateKeyMod);
                        
                      //  dd($privateKey,$key);
                        
                        if (!$ssh->login('root', $key)) {
                            throw new \Exception('SSH authentication failed');
                        }

                        // Disable password authentication
                        $ssh->exec("sed -i 's/PasswordAuthentication yes/PasswordAuthentication no/g' /etc/ssh/sshd_config");
                        $ssh->exec("systemctl restart sshd");

                        // Update server status
                        $record->update([
                            'server_status' => 'connected'
                        ]);

                        Notification::make()
                            ->success()
                            ->title("Successfully connected to server {$record->controlpanel_server_id}")
                            ->send();

                  /*  } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title("Failed to connect to server {$record->controlpanel_server_id}")
                            ->body($e->getMessage())
                            ->send();
                    } 
                    */
                }),

                Tables\Actions\Action::make('create_application')
                    ->label('Create Application')
                    ->icon('heroicon-o-plus')
                    ->url(fn (Server $record) => route('filament.dashboard.resources.applications.create', ['server_id' => $record->id]))
                    ->visible(fn (Server $record) => $record->server_status === 'connected'),

                Tables\Actions\Action::make('manage_server')
                    ->label('Manage Server')
                    ->icon('heroicon-o-cog')
                    ->url(fn (Server $record) => ServerResource::getUrl('manage', ['record' => $record]))
                    ->visible(fn (Server $record) => $record->server_status === 'connected'),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServers::route('/'),
            'create' => Pages\CreateServer::route('/create'),
            'view' => Pages\ViewServer::route('/{record}'),
            'edit' => Pages\EditServer::route('/{record}/edit'),
            'manage' => Pages\ManageServer::route('/{record}/manage'),
        ];
    }
    
protected static function updateProviderOptions($controlPanelId, callable $set): void 
{
    if (!$controlPanelId) {
        $set('provider_id', null);
        return;
    }

    // Get the control panel
    $controlPanel = \App\Models\ServerControlPanel::find($controlPanelId);
    
    if (!$controlPanel || !$controlPanel->available_providers) {
        $set('provider_id', null);
        return;
    }

    // Reset the provider selection
    $set('provider_id', null);

    // Convert the providers to the correct format
    // Since available_providers is in format {"584":"digitalocean","1489":"vultr",...}
    $providers = collect($controlPanel->available_providers)
        ->map(function ($name, $id) {
            return [
                'id' => $id,
                'name' => $name
            ];
        })
        ->pluck('name', 'id')
        ->toArray();

    // Set the new provider options
    $set('provider_options', $providers);
}



protected static function getRegionOptions($controlPanel, $providerId): array
{
    try {
        $credentials = $controlPanel->getCredentials();
        $apiToken = unserialize($credentials['api_token']);
        $service = app(ControlPanelServiceFactory::class)->create($controlPanel->type, $apiToken);
        
        return $service->getControlPanelRegions($providerId);
    } catch (\Exception $e) {
        \Log::error('Failed to fetch regions', [
            'error' => $e->getMessage(),
            'control_panel' => $controlPanel->id,
            'provider' => $providerId
        ]);
        return [];
    }
}

protected static function getPlanOptions($controlPanel, $providerId, $region): array
{
    try {
        $credentials = $controlPanel->getCredentials();
        $apiToken = unserialize($credentials['api_token']);
        $service = app(ControlPanelServiceFactory::class)->create($controlPanel->type, $apiToken);
        
        return $service->getControlPanelPlans($providerId, $region);
    } catch (\Exception $e) {
        \Log::error('Failed to fetch plans', [
            'error' => $e->getMessage(),
            'control_panel' => $controlPanel->id,
            'provider' => $providerId,
            'region' => $region
        ]);
        return [];
    }
}

  /*
  
    protected static function updateProviderOptions($controlPanelId, callable $set): void 
{
    if (!$controlPanelId) {
        $set('provider_id', null);
        return;
    }

    // Get the control panel and its available providers
    $controlPanel = \App\Models\ServerControlPanel::find($controlPanelId);
    
    if (!$controlPanel || !$controlPanel->available_providers) {
        $set('provider_id', null);
        return;
    }

    // Reset the provider selection since we're changing the available options
    $set('provider_id', null);

    // Update the provider options based on the selected control panel
    $providers = collect($controlPanel->available_providers)->map(function ($provider) {
        return [
            'id' => $provider['id'],
            'name' => $provider['name'] ?? $provider['id']
        ];
    })->pluck('name', 'id')->toArray();

    // Set the new provider options
    $set('provider_options', $providers);
}
    */
    
    
    
    
    
}