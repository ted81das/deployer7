<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\ApplicationResource\Pages;
use App\Models\Application;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

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
        ];
    }
}