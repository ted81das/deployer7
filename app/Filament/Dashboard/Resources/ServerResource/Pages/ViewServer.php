<?php

namespace App\Filament\Dashboard\Resources\ServerResource\Pages;

use App\Filament\Dashboard\Resources\ServerResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;

class ViewServer extends ViewRecord
{
   
   
    protected static string $resource = ServerResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Server Information')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('controlPanel.name')
                                    ->label('Control Panel'),

                                TextEntry::make('name')
                                    ->label('Server Name'),

                                TextEntry::make('provider.name')
                                    ->label('Provider'),

                                TextEntry::make('region')
                                    ->label('Region'),

                                TextEntry::make('plan')
                                    ->label('Plan'),

                                TextEntry::make('web_server')
                                    ->label('Web Server'),

                                TextEntry::make('database_type')
                                    ->label('Database Type'),

                                TextEntry::make('server_ip')
                                    ->label('Server IP'),

                                TextEntry::make('hostname')
                                    ->label('Hostname'),
                                    
                                    
                                    
                                    TextEntry::make('controlPanel.name')
                                    ->label('Control Panel'),

                                TextEntry::make('name')
                                    ->label('Server Name'),

                                TextEntry::make('provider.name')
                                    ->label('Provider'),

                                TextEntry::make('region')
                                    ->label('Region'),

                                TextEntry::make('plan')
                                    ->label('Plan'),

                                TextEntry::make('web_server')
                                    ->label('Web Server')
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'nginx' => 'Nginx',
                                        'apache2' => 'Apache',
                                        'openlitespeed' => 'LiteSpeed',
                                        'mern' => 'MERN',
                                        default => $state,
                                    }),

                                TextEntry::make('database_type')
                                    ->label('Database Type')
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        'mariadb' => 'MariaDB',
                                        'mysql' => 'MySQL',
                                        default => $state,
                                    }),

                                // Additional server details
                                TextEntry::make('server_ip')
                                    ->label('Server IP'),

                                TextEntry::make('hostname')
                                    ->label('Hostname'),

                                TextEntry::make('operating_system')
                                    ->label('Operating System'),

                                TextEntry::make('server_status')
                                    ->label('Server Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'active' => 'success',
                                        'pending' => 'warning',
                                        'failed' => 'danger',
                                        default => 'secondary',
                                    }),

                                TextEntry::make('provisioning_status')
                                    ->label('Provisioning Status'),

                                TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->dateTime(),
               
                            ]),
                    ]),
            ]);
    }
   
   
   
}