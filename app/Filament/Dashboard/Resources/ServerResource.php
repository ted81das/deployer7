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
                                ->afterStateUpdated(fn ($state, callable $set) => 
                                    static::updateProviderOptions($state, $set)),

                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255),

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
                    ->label('Control Panel'),
                Tables\Columns\BadgeColumn::make('provisioning_status')
                    ->colors([
                        'danger' => 'failed',
                        'warning' => ['pending', 'provisioning'],
                        'success' => 'active',
                    ]),
                Tables\Columns\BadgeColumn::make('server_status')
                    ->label('Connection Status')
                    ->colors([
                        'danger' => 'failed',
                        'warning' => 'pending',
                        'success' => 'connected',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('check_status')
                    ->label('Check Status')
                    ->icon('heroicon-o-arrow-path')
                    ->visible(fn (Server $record) => $record->provisioning_status === 'pending')
                    ->action(fn (Server $record) => $record->checkServerStatus()),

                Tables\Actions\Action::make('create_application')
                    ->label('Create Application')
                    ->icon('heroicon-o-plus')
                    ->url(fn (Server $record) => route('filament.resources.applications.create', ['server_id' => $record->id]))
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
       //     'manage' => Pages\ManageServer::route('/{record}/manage'),
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
