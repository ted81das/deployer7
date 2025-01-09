<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\ApplicationTypeResource\Pages;
use App\Filament\Dashboard\Resources\ApplicationTypeResource\RelationManagers;
use App\Models\ApplicationType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ApplicationTypeResource extends Resource
{
    protected static ?string $model = ApplicationType::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('type')
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')
                    ->required(),
                Forms\Components\Toggle::make('is_global')
                    ->required(),
                Forms\Components\Toggle::make('is_git_supported')
                    ->required(),
                Forms\Components\TextInput::make('git_deployment_url')
                    ->maxLength(255),
                Forms\Components\TextInput::make('default_branch')
                    ->required()
                    ->maxLength(255)
                    ->default('main'),
                Forms\Components\Textarea::make('deployment_script_template')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('post_deployment_script')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('environment_template')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('required_php_extensions'),
                Forms\Components\TextInput::make('required_dependencies'),
                Forms\Components\TextInput::make('minimum_php_version')
                    ->maxLength(255),
                Forms\Components\TextInput::make('recommended_php_version')
                    ->maxLength(255),
                Forms\Components\TextInput::make('supported_databases'),
                Forms\Components\TextInput::make('default_web_server')
                    ->maxLength(255),
                Forms\Components\TextInput::make('configuration_options'),
                Forms\Components\TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('icon_path')
                    ->maxLength(255),
                Forms\Components\TextInput::make('documentation_url')
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_git')
                    ->required(),
                Forms\Components\TextInput::make('file_name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('git_provider_id')
                    ->numeric(),
                Forms\Components\TextInput::make('repository')
                    ->maxLength(255),
                Forms\Components\TextInput::make('username')
                    ->maxLength(255),
                Forms\Components\TextInput::make('repository_name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('branch')
                    ->maxLength(255),
                Forms\Components\TextInput::make('allowed_web_server_types'),
                Forms\Components\Textarea::make('cloudpanel_curl')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('deployment_script')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_cloud_curl_script')
                    ->required(),
                Forms\Components\Toggle::make('has_cli')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_global')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_git_supported')
                    ->boolean(),
                Tables\Columns\TextColumn::make('git_deployment_url')
                    ->searchable(),
                Tables\Columns\TextColumn::make('default_branch')
                    ->searchable(),
                Tables\Columns\TextColumn::make('minimum_php_version')
                    ->searchable(),
                Tables\Columns\TextColumn::make('recommended_php_version')
                    ->searchable(),
                Tables\Columns\TextColumn::make('default_web_server')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('icon_path')
                    ->searchable(),
                Tables\Columns\TextColumn::make('documentation_url')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_git')
                    ->boolean(),
                Tables\Columns\TextColumn::make('file_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('git_provider_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('repository')
                    ->searchable(),
                Tables\Columns\TextColumn::make('username')
                    ->searchable(),
                Tables\Columns\TextColumn::make('repository_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('branch')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_cloud_curl_script')
                    ->boolean(),
                Tables\Columns\IconColumn::make('has_cli')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListApplicationTypes::route('/'),
            'create' => Pages\CreateApplicationType::route('/create'),
            'view' => Pages\ViewApplicationType::route('/{record}'),
            'edit' => Pages\EditApplicationType::route('/{record}/edit'),
        ];
    }
}
