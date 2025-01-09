<?php

namespace App\Filament\Dashboard\Resources\ServerResource\Pages;

use App\Filament\Dashboard\Resources\ServerResource;
use App\Models\Server;
use Filament\Resources\Pages\Page;
use Filament\Dashboard\Pages;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;

class ManageServer extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = ServerResource::class;
    
    protected static string $view = 'filament.dashboard.pages.manage-server';
 
    public ?Server $record = null;
    
       
    // Add this property to make server available to the view
    public Server $server;
    

    public function mount(Server $record): void
    {
        $this->record = $record;
        $this->form->fill($record->toArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()
                    ->schema([
                        Section::make('Server Information')
                            ->schema([
                                TextInput::make('name')
                                    ->disabled()
                                    ->default($this->record->name),
                                
                                TextInput::make('hostname')
                                    ->disabled()
                                    ->default($this->record->hostname),
                                
                                TextInput::make('server_ip')
                                    ->disabled()
                                    ->default($this->record->server_ip),
                                
                                TextInput::make('php_version')
                                    ->required(),
                                
                                Select::make('web_server')
                                    ->options([
                                        'nginx' => 'Nginx',
                                        'apache2' => 'Apache',
                                        'openlitespeed' => 'LiteSpeed',
                                        'mern' => 'MERN'
                                    ])
                                    ->default($this->record->web_server)
                                    ->required(),
                                
                                Select::make('database_type')
                                    ->options([
                                        'mariadb' => 'MariaDB',
                                        'mysql' => 'MySQL'
                                    ])
                                    ->default($this->record->database_type)
                                    ->required(),
                            ])
                            ->columns(2),
                    ]),
            ]);
    }
}