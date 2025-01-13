<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\ApplicationResource\Pages;
use App\Models\Application;
use App\Models\Server;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use App\Services\SSHConnectionService;
use Filament\Notifications\Notification;
use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;
use Illuminate\Support\Facades\Crypt;

class ApplicationResource extends Resource
{
    protected static ?string $model = Application::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Section::make('Basic Information')
                        ->schema([
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
                                ->required()
                                ->reactive(),

                            Forms\Components\Select::make('mapped_plan')
                                ->options([
                                    'starter' => 'Starter',
                                    'advanced' => 'Advanced',
                                    'premium' => 'Premium'
                                ])
                                ->required()
                                ->reactive(),

                            Forms\Components\Select::make('server_id')
                                ->label('Server')
                                ->options(function (callable $get) {
                                    return \App\Models\Server::query()
                                        ->where('mapped_region', $get('mapped_region'))
                                        ->where('mapped_plan', $get('mapped_plan'))
                                        ->where(function (Builder $query) {
                                            $query->where('user_id', auth()->id())
                                                ->orWhere('owned_by', auth()->id());
                                        })
                                        ->pluck('name', 'id');
                                })
                                ->required()
                                ->searchable()
                                ->reactive(),

                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\TextInput::make('hostname')
                                ->required()
                                ->maxLength(255),

                            Forms\Components\Select::make('php_version')
                                ->options([
                                    '8.3' => 'PHP 8.3',
                                    '8.2' => 'PHP 8.2',
                                    '8.1' => 'PHP 8.1',
                                    '7.4' => 'PHP 7.4',
                                ])
                                ->required(),

                            Forms\Components\Select::make('web_server')
                                ->options([
                                    'apache' => 'Apache',
                                    'nginx' => 'Nginx',
                                    'litespeed' => 'LiteSpeed',
                                ])
                                ->required(),
                        ])->columns(2),

                    Forms\Components\Section::make('Admin Credentials')
                        ->schema([
                            Forms\Components\TextInput::make('admin_user')
                                ->maxLength(255),
                            
                            Forms\Components\TextInput::make('admin_password')
                                ->password()
                                ->dehydrateStateUsing(fn ($state) => $state ? encrypt($state) : null),
                            
                            Forms\Components\TextInput::make('admin_email')
                                ->email()
                                ->maxLength(255),
                        ])->columns(2),
                        
                        Forms\Components\Section::make('System User Configuration')
    ->schema([
        Forms\Components\TextInput::make('system_user')
            ->required()
            ->default(fn () => Str::lower(Str::random(10)))
            ->helperText('System username (10 characters)')
            ->maxLength(255),
        
        Forms\Components\TextInput::make('system_user_password')
            ->required()
            ->default(fn () => Str::lower(Str::random(12)))
            ->password()
            ->helperText('System user password (12 characters)')
            ->dehydrateStateUsing(fn ($state) => $state ? encrypt($state) : null),
            
        Forms\Components\TextInput::make('web_root')
            ->default('/public_html')
            ->helperText('Web root directory like public for laravel')
            ->maxLength(255),
    ])->columns(2),

                    Forms\Components\Section::make('Database Configuration')
                        ->schema([
                            Forms\Components\Toggle::make('is_existing_database')
                                ->label('Use Existing Database')
                                ->reactive(),

                            Forms\Components\Toggle::make('generate_random_dbcredential')
                                ->label('Generate Random Credentials')
                                ->reactive()
                                ->visible(fn (callable $get) => !$get('is_existing_database')),

                            Forms\Components\TextInput::make('database_name')
                                ->maxLength(255)
                                ->visible(fn (callable $get) => $get('is_existing_database')),

                            Forms\Components\TextInput::make('database_user')
                                ->maxLength(255)
                                ->visible(fn (callable $get) => $get('is_existing_database')),

                            Forms\Components\TextInput::make('database_password')
                                ->password()
                                ->dehydrateStateUsing(fn ($state) => $state ? encrypt($state) : null)
                                ->visible(fn (callable $get) => $get('is_existing_database')),

                            Forms\Components\TextInput::make('database_host')
                                ->maxLength(255)
                                ->visible(fn (callable $get) => $get('is_existing_database')),
                        ])->columns(2),

                    Forms\Components\Section::make('Git Configuration')
                        ->schema([
                            Forms\Components\TextInput::make('git_repository')
                                ->maxLength(255),
                            
                            Forms\Components\TextInput::make('git_branch')
                                ->maxLength(255)
                                ->default('main'),
                            
                            Forms\Components\TextInput::make('git_provider')
                                ->maxLength(255),
                        ])->columns(2),

                    Forms\Components\Section::make('PHP-FPM Configuration')
                        ->schema([
                            Forms\Components\Select::make('pm_type')
                                ->options([
                                    'ondemand' => 'On Demand',
                                    'dynamic' => 'Dynamic',
                                    'static' => 'Static',
                                ])
                                ->required(),

                            Forms\Components\TextInput::make('pm_max_children')
                                ->numeric()
                                ->default(20)
                                ->required(),

                            // Add other PHP-FPM configuration fields...
                            // Similar pattern for remaining fields
                        ])->columns(2),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('hostname')
                    ->searchable(),
                Tables\Columns\TextColumn::make('server.name')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('deployment_status')
                    ->colors([
                        'danger' => 'failed',
                        'warning' => 'pending',
                        'success' => 'deployed',
                    ]),
                Tables\Columns\BadgeColumn::make('app_status')
                    ->colors([
                        'danger' => ['failed', 'connection_failed'],
                        'warning' => 'pending',
                        'success' => 'connected',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(),
            ])
            ->filters([
                //
            ])
            
            ->actions([
            // Manage App - only when deployment complete and app connected
            Tables\Actions\Action::make('manage')
                ->label('Manage App')
                ->icon('heroicon-o-cog')
                ->url(fn (Application $record) => route('filament.dashboard.resources.applications.manage', $record))
                ->visible(fn (Application $record) => 
                    $record->deployment_status === 'complete' && 
                    $record->app_status === 'connected'
                ),

            // Connect - only when deployment complete and app pending
            Tables\Actions\Action::make('connect')
                ->label('Connect')
                ->icon('heroicon-o-link')
                ->visible(fn (Application $record) => 
        $record->deployment_status === 'complete' && 
        ($record->app_status === 'pending' || $record->app_status === 'ssl_failed')
    )
                ->action(function (Application $record) {
                    try {
                        if (empty($record->application_sshkey_private)) {
                            throw new \Exception('SSH private key not found');
                        }

                        $sshService = new SSHConnectionService();
                        $privateKey = $record->application_sshkey_private;
                      //   $privateKey = $record->server_sshkey_private;
                 
                     /*   $key = PublicKeyLoader::load($privateKey);
                     
                       $ssh = new SSH2($record->server->server_ip);
                
                      
                      $connected = $ssh->login($record->system_user, $key);
                      */
                       $connected = $sshService->testConnection(
                $record->server->server_ip,
                $record->system_user,
                $privateKey
            );

                 
                        if (!$connected) {
                            throw new \Exception('SSH authentication failed');
                        }
                        

                        if ($connected) {
                            $record->update(['app_status' => 'connected']);
                            
                            Notification::make()
                                ->title('Connection Successful')
                                ->success()
                                ->body('Successfully connected to application server.')
                                ->send();
                        } else {
                            Notification::make()
                                ->title('Connection Failed')
                                ->danger()
                                ->body('Failed to establish SSH connection.')
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Connection Error')
                            ->danger()
                            ->body('Error: ' . $e->getMessage())
                            ->send();
                            
                        \Log::error('SSH connection failed: ' . $e->getMessage());
                    }
                }),

            // Retry SSL - only when app_status is ssl_failed
            Tables\Actions\Action::make('retry_ssl')
                ->label('Retry SSL')
                ->icon('heroicon-o-shield-check')
                ->visible(fn (Application $record) => $record->app_status === 'ssl_failed')
                ->action(function (Application $record) {
                    $server = $record->server;
                    $controlPanelService = $server->getControlPanelService();

                    try {
                        $sslResponse = $controlPanelService->installSSL([
                            'server_id' => $server->controlpanel_server_id,
                            'application_id' => $record->controlpanel_app_id,
                            'domain' => $record->hostname,
                            'type' => 'letsencrypt'
                        ]);

                        if (!$sslResponse) {
                            Notification::make()
                                ->title('SSL Installation Warning')
                                ->warning()
                                ->body('SSL installation may have failed or is pending.')
                                ->send();
                        } else {
                            $record->update(['app_status' => 'pending']);
                            
                            Notification::make()
                                ->title('SSL Certificate Installed')
                                ->success()
                                ->body('SSL certificate has been installed successfully!')
                                ->send();
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('SSL Installation Failed')
                            ->warning()
                            ->body('SSL installation failed: ' . $e->getMessage())
                            ->send();
                        
                        $record->update(['app_status' => 'ssl_failed']);
                        \Log::warning('SSL installation failed: ' . $e->getMessage());
                    }
                }),
            
     //       ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Application $record) => $record->app_status === 'connected'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApplications::route('/'),
            'create' => Pages\CreateApplication::route('/create'),
            'view' => Pages\ViewApplication::route('/{record}'),
            'edit' => Pages\EditApplication::route('/{record}/edit'),
            'manage' => Pages\ManageApplication::route('/{record}/manage'),
        ];
    }
}