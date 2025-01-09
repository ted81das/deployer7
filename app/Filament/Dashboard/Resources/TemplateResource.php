<?php

namespace App\Filament\Dashboard\Resources;

use App\Filament\Dashboard\Resources\TemplateResource\Pages;
use App\Filament\Dashboard\Resources\TemplateResource\RelationManagers;
use App\Models\Template;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TemplateResource extends Resource
{
    protected static ?string $model = Template::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('uuid')
                    ->label('UUID')
                    ->required()
                    ->maxLength(36),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('type')
                    ->required(),
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Forms\Components\TextInput::make('git_type')
                    ->required(),
                Forms\Components\TextInput::make('file_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('username')
                    ->maxLength(255),
                Forms\Components\TextInput::make('repository_name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('repository')
                    ->maxLength(255),
                Forms\Components\TextInput::make('git_provider_id')
                    ->maxLength(255),
                Forms\Components\TextInput::make('clone_url')
                    ->maxLength(255),
                Forms\Components\TextInput::make('workspace_slug')
                    ->maxLength(255),
                Forms\Components\TextInput::make('repository_slug')
                    ->maxLength(255),
                Forms\Components\TextInput::make('project_id')
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('git_branch')
                    ->required()
                    ->maxLength(255)
                    ->default('main'),
                Forms\Components\TextInput::make('git_provider')
                    ->maxLength(255),
                Forms\Components\TextInput::make('default_admin_user')
                    ->maxLength(255),
                Forms\Components\TextInput::make('default_admin_email')
                    ->email()
                    ->maxLength(255),
                Forms\Components\Textarea::make('default_admin_password')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('deployment_script')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('post_deployment_script')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')
                    ->required(),
                Forms\Components\TextInput::make('settings'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type'),
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('git_type'),
                Tables\Columns\TextColumn::make('file_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('username')
                    ->searchable(),
                Tables\Columns\TextColumn::make('repository_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('repository')
                    ->searchable(),
                Tables\Columns\TextColumn::make('git_provider_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('clone_url')
                    ->searchable(),
                Tables\Columns\TextColumn::make('workspace_slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('repository_slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('project_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('git_branch')
                    ->searchable(),
                Tables\Columns\TextColumn::make('git_provider')
                    ->searchable(),
                Tables\Columns\TextColumn::make('default_admin_user')
                    ->searchable(),
                Tables\Columns\TextColumn::make('default_admin_email')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
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
            'index' => Pages\ListTemplates::route('/'),
            'create' => Pages\CreateTemplate::route('/create'),
            'view' => Pages\ViewTemplate::route('/{record}'),
            'edit' => Pages\EditTemplate::route('/{record}/edit'),
        ];
    }
}
