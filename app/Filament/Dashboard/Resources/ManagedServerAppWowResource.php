<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\ManagedServerAppWowResource\Pages;
use App\Filament\Dashboard\Resources\ManagedServerAppWowResource\RelationManagers;
use App\Models\ManagedServerAppWow;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use Filament\Tables\Columns\IconColumn;
use App\Services\SSHConnectionService;
use App\Jobs\DeployApplicationJob;
use Filament\Tables\Actions\Action;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Model;
//use Illuminate\Database\Eloquent\Builder;

class ManagedServerAppWowResource extends Resource
{
    protected static ?string $model = ManagedServerAppWow::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

 public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                // Basic Application Details
                                Forms\Components\TextInput::make('userslug')
                                    ->required()
                                    ->unique(ignorable: fn ($record) => $record)
                                    ->regex('/^[a-zA-Z0-9]{5}$/')
                                    ->helperText('5 character alphanumeric identifier')
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('application_name')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('app_hostname')
                                    ->required()
                                    ->unique(ignorable: fn ($record) => $record)
                                    ->maxLength(255)
                                    ->helperText('e.g., myapp.domain.com')
                                    ->columnSpan(2),

                                // WordPress Admin Details
                                Forms\Components\TextInput::make('app_miniadmin_username')
                                    ->required()
                                    ->maxLength(255)
                                    ->label('WordPress Admin Username')
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('app_miniadmin_email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->label('WordPress Admin Email')
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('app_miniadmin_password')
                                    ->password()
                                    ->required()
                                    ->minLength(12)
                                    ->label('WordPress Admin Password')
                                    ->columnSpan(2),

                                // Git Repository Details
                                Forms\Components\TextInput::make('clone_url')
                                    ->required()
                                    ->url()
                                    ->default('https://github.com/ted81das/newxtwowai-1-tbd.git')
                                    ->maxLength(255)
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('branch')
                                    ->default('main')
                                    ->required()
                                    ->columnSpan(1),

                                // PHP Version Selection
                                Forms\Components\Select::make('php_version')
                                    ->options([
                                        '8.1' => 'PHP 8.1',
                                        '8.2' => 'PHP 8.2',
                                    ])
                                    ->default('8.1')
                                    ->required()
                                    ->columnSpan(1),
                            ]),
                    ])
            ]);
    }

   
        public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('application_name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('app_hostname')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => "https://{$record->app_hostname}")
                    ->openUrlInNewTab(),

                IconColumn::make('phpseclib_connection_status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->label('Connection Status'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                

                Action::make('verify_connection')
                    ->icon('heroicon-o-arrow-path')
                    ->action(fn ($record) => $record->updateConnectionStatus())
                    ->visible(fn ($record) => !empty($record->serveravatar_application_id)),
                
               Action::make('manage')
                ->icon('heroicon-o-cog')
                ->url(fn ($record) => $record->phpseclib_connection_status 
                    ? route('filament.dashboard.resources.managed-server-app-wows.manage', $record) 
                    : null)
                ->disabled(fn ($record) => !$record->phpseclib_connection_status),  
                
               /* Action::make('manage')
                    ->url(fn (ManagedServerAppWow $record): string => 
                        $record->phpseclib_connection_status 
                            ? static::getUrl('manage-wp-cli', ['record' => $record]) 
                            : '#'
                    )
                    ->disabled(fn (ManagedServerAppWow $record): bool => 
                        !$record->phpseclib_connection_status
                    )
                    ->icon('heroicon-s-cog')
                    ->tooltip('Manage Application'),*/
                    
                    

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListManagedServerAppWows::route('/'),
            'create' => Pages\CreateManagedServerAppWow::route('/create'),
            'view' => Pages\ViewManagedServerAppWow::route('/{record}'),
            'edit' => Pages\EditManagedServerAppWow::route('/{record}/edit'),
            // Add this new route for manage
        'manage' => Pages\ManageApplication::route('/{record}/manage'),
        ];
    }

  

   protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate system user credentials
        $randomUsername = str($data['userslug'] . '_wow_' . Str::random(10))->lower();
        $randomPassword = Str::random(12);
        
        // Generate database credentials
        $dbName = str($data['userslug'] . '_wow_' . Str::random(10))->lower();
        $dbUsername = str($data['userslug'] . '_wow_' . Str::random(10))->lower();
        $dbPassword = Str::random(12);

        // Generate SSH keys using phpseclib
        $sshService = app(SSHConnectionService::class);
        $keyPair = $sshService->generateKeyPair($randomUsername);

        // Store keys in storage
        $keyPath = "keys/{$randomUsername}";
        Storage::disk('local')->put("{$keyPath}/id_rsa_{$randomUsername}", $keyPair['privateKey']);
        Storage::disk('local')->put("{$keyPath}/id_rsa_{$randomUsername}.pub", $keyPair['publicKey']);

        // Prepare ServerAvatar API payload
        $apiPayload = [
            'name' => $data['application_name'],
            'method' => 'git',
            'framework' => 'github',
            'hostname' => $data['app_hostname'],
            'systemUser' => 'new',
            'systemUserInfo' => [
                'username' => $randomUsername,
                'password' => $randomPassword
            ],
            'php_version' => $data['php_version'],
            'webroot' => '',
            'www' => false,
            'type' => 'public',
            'git_provider_id' => config('services.serveravatar.git_provider_id'),
            'clone_url' => $data['clone_url'],
            'branch' => $data['branch'],
            'script' => $this->generateWpCliScript($data, $dbName, $dbUsername, $dbPassword)
        ];

        // Make API call to ServerAvatar
        try {
            $client = new Client();
            $response = $client->post(
                config('services.serveravatar.api_url') . '/applications',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . config('services.serveravatar.api_token'),
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $apiPayload
                ]
            );

            $serverAvatarResponse = json_decode($response->getBody(), true);

            // Merge all data
            return array_merge($data, [
                'application_user' => $randomUsername,
                'system_password' => $randomPassword,
                'db_name' => $dbName,
                'db_username' => $dbUsername,
                'db_password' => $dbPassword,
                'serveravatar_application_id' => $serverAvatarResponse['id'] ?? null,
                'deployment_status' => $serverAvatarResponse['status'] ?? 'pending',
                'ssh_key_path' => $keyPath,
                'phpseclib_connection_status' => false
            ]);

        } catch (\Exception $e) {
            throw new \Exception('Failed to create application: ' . $e->getMessage());
        }
    }

    protected function generateWpCliScript(array $data, string $dbName, string $dbUsername, string $dbPassword): string
    {
        return implode("\n", [
            "wp config create --dbname=\"{$dbName}\" --dbuser=\"{$dbUsername}\" --dbpass=\"{$dbPassword}\" --dbhost=\"localhost\";",
            
            "wp core install --url=\"{$data['app_hostname']}\" --title=\"{$data['application_name']}\" " .
            "--admin_user=\"{$data['app_miniadmin_username']}\" --admin_password=\"{$data['app_miniadmin_password']}\" " .
            "--admin_email=\"{$data['app_miniadmin_email']}\";",
            
            "wp db import nextwowtry2-2024-12-01-da6c6a5.sql;",
            
            "wp search-replace \"nextwowtry2.test\" \"{$data['app_hostname']}\";",
        ]);
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::count() > 0 ? 'success' : 'warning';
    }

   /* public static function canCreate(): bool  {
        return auth()->user()->can('create_wow_applications');
    }*/

    /*public static function canEdit(Model $record): bool {
        return false; // Disable editing as per requirements
    }*/

 

    public function getTableRecordUrlUsing(): ?\Closure
    {
        return fn (Model $record): string => static::getUrl('view', ['record' => $record]);
    }

   public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['application_name', 'app_hostname', 'application_user'];
    }


  
  
}
