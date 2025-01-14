<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CliFrameworkResource\Pages;
use App\Filament\Admin\Resources\CliFrameworkResource\RelationManagers;
use App\Models\CliFramework;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Unique;

class CliFrameworkResource extends Resource
{
    protected static ?string $model = CliFramework::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('uuid')
                ->dehydrateStateUsing(function ($state) {
                    if (empty($state)) {
                        // Generate random 10 char lowercase string
                        do {
                            $uuid = Str::lower(Str::random(10));
                        } while (static::$model::where('uuid', $uuid)->exists());
                        
                        return $uuid;
                    }
                    return $state;
                })
                ->disabled()
                ->dehydrated(true)
                ->unique(ignoreRecord: true)
                ->helperText('Automatically generated unique identifier')
                ->maxLength(10),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('startcharacters')
                    ->maxLength(255),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('startcharacters')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
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
            'index' => Pages\ListCliFrameworks::route('/'),
            'create' => Pages\CreateCliFramework::route('/create'),
            'view' => Pages\ViewCliFramework::route('/{record}'),
            'edit' => Pages\EditCliFramework::route('/{record}/edit'),
        ];
    }
}
