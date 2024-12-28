<?php

namespace App\Filament\Admin\Resources;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->helperText('The name of the role.')
                        ->disabled(fn (?Model $record) => $record && $record->name === 'admin')
                        ->maxLength(255),
                    Forms\Components\Select::make('permissions')
                        ->disabled(fn (?Model $record) => $record && $record->name === 'admin')
                        ->relationship('permissions', 'name')
                        ->multiple()
                        ->preload()
                        ->helperText('Choose the permissions for this role.')
                        ->placeholder(__('Select permissions...')),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime(config('app.datetime_format'))->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime(config('app.datetime_format'))->sortable(),
            ])
            ->filters([

            ])
            ->actions([
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
            'index' => \App\Filament\Admin\Resources\RoleResource\Pages\ListRoles::route('/'),
            'create' => \App\Filament\Admin\Resources\RoleResource\Pages\CreateRole::route('/create'),
            'edit' => \App\Filament\Admin\Resources\RoleResource\Pages\EditRole::route('/{record}/edit'),
        ];
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getNavigationLabel(): string
    {
        return __('Roles');
    }
}
