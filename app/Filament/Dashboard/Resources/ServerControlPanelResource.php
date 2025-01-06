<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\ServerControlPanelResource\Pages;
use App\Filament\Dashboard\Resources\ServerControlPanelResource\RelationManagers;
use App\Models\ServerControlPanel;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ServerControlPanelResource extends Resource
{
    protected static ?string $model = ServerControlPanel::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\Select::make('type')
                    ->options([
                        'serveravatar' => 'ServerAvatar',
                        'cloudways' => 'Cloudways',
                        'ploi' => 'Ploi',
                        'spinupwp' => 'SpinupWP',
                        'forge' => 'Forge'
                    ])
                    ->required()
                    ->reactive(),

                // Cloudways specific fields
                Forms\Components\TextInput::make('api_client')
                    ->required(fn (callable $get) => $get('type') === 'cloudways')
                    ->visible(fn (callable $get) => $get('type') === 'cloudways'),

                Forms\Components\TextInput::make('api_secret')
                    ->required(fn (callable $get) => $get('type') === 'cloudways')
                    ->visible(fn (callable $get) => $get('type') === 'cloudways')
                    ->password(),

                // Non-Cloudways API token field
                Forms\Components\TextInput::make('api_token')
                    ->required(fn (callable $get) => $get('type') !== 'cloudways')
                    ->visible(fn (callable $get) => $get('type') !== 'cloudways')
                    ->password(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge(),
                Tables\Columns\BadgeColumn::make('authentication_status')
                    ->colors([
                        'danger' => 'failed',
                        'warning' => 'pending_authentication',
                        'success' => 'authenticated',
                    ]),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('last_authenticated_at')
                    ->dateTime(),
            ])
            ->actions([
                // Authenticate Action
                Tables\Actions\Action::make('authenticate')
                    ->icon('heroicon-o-key')
                    ->visible(fn (ServerControlPanel $record) => 
                        $record->authentication_status !== 'authenticated')
                    ->action(function (ServerControlPanel $record) {
                        try {
                            $serviceClass = "App\\Services\\ControlPanel\\" . 
                                ucfirst($record->type) . "Service";
                            $service = app($serviceClass);
                            
                            if ($service->authenticateControlPanel($record)) {
                                $record->update([
                                    'authentication_status' => 'authenticated',
                                    'last_authenticated_at' => now(),
                                    'authentication_error' => null
                                ]);
                            } else {
                                throw new \Exception('Authentication failed');
                            }
                        } catch (\Exception $e) {
                            $record->update([
                                'authentication_status' => 'failed',
                                'authentication_error' => $e->getMessage()
                            ]);
                            throw $e;
                        }
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServerControlPanels::route('/'),
            'create' => Pages\CreateServerControlPanel::route('/create'),
            'edit' => Pages\EditServerControlPanel::route('/{record}/edit'),
            'view' => Pages\ViewServerControlPanel::route('/{record}'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Set default auth_type based on control panel type
        $data['auth_type'] = $data['type'] === 'cloudways' ? 'oauth2' : 'bearer';

        // Encrypt sensitive data
        if (isset($data['api_token'])) {
            $data['api_token'] = encrypt($data['api_token']);
        }
        if (isset($data['api_secret'])) {
            $data['api_secret'] = encrypt($data['api_secret']);
        }

        // Clear irrelevant fields based on type
        if ($data['type'] === 'cloudways') {
            $data['api_token'] = null;
        } else {
            $data['api_client'] = null;
            $data['api_secret'] = null;
        }

        return $data;
    }
}
