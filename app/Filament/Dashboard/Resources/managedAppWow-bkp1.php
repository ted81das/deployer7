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

class ManagedServerAppWowResource extends Resource
{
    protected static ?string $model = ManagedServerAppWow::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('uuid')
                    ->label('UUID')
                    ->required()
                    ->maxLength(36),
                Forms\Components\TextInput::make('application_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('userslug')
                    ->required()
                    ->maxLength(5),
                Forms\Components\TextInput::make('app_hostname')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('app_miniadmin_username')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('app_miniadmin_email')
                    ->email()
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('app_miniadmin_password')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('application_user')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('system_password')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('application_user_id')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('system_user_info'),
                Forms\Components\TextInput::make('db_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('db_username')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('db_password')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('php_version')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('webroot')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('git_provider_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('clone_url')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('branch')
                    ->required()
                    ->maxLength(255)
                    ->default('main'),
                Forms\Components\Toggle::make('phpseclib_connection_status')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('application_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('userslug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('app_hostname')
                    ->searchable(),
                Tables\Columns\TextColumn::make('app_miniadmin_username')
                    ->searchable(),
                Tables\Columns\TextColumn::make('app_miniadmin_email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('application_user')
                    ->searchable(),
                Tables\Columns\TextColumn::make('application_user_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('db_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('db_username')
                    ->searchable(),
                Tables\Columns\TextColumn::make('php_version')
                    ->searchable(),
                Tables\Columns\TextColumn::make('webroot')
                    ->searchable(),
                Tables\Columns\TextColumn::make('git_provider_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('clone_url')
                    ->searchable(),
                Tables\Columns\TextColumn::make('branch')
                    ->searchable(),
                Tables\Columns\IconColumn::make('phpseclib_connection_status')
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
            'index' => Pages\ListManagedServerAppWows::route('/'),
            'create' => Pages\CreateManagedServerAppWow::route('/create'),
            'view' => Pages\ViewManagedServerAppWow::route('/{record}'),
            'edit' => Pages\EditManagedServerAppWow::route('/{record}/edit'),
        ];
    }
}
