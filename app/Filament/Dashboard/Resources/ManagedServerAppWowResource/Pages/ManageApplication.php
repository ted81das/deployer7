<?php

namespace App\Filament\Dashboard\Resources\ManagedServerAppWowResource\Pages;

use App\Filament\Dashboard\Resources\ManagedServerAppWowResource;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Model;

class ManageApplication extends Page
{
    protected static string $resource = ManagedServerAppWowResource::class;

    protected static string $view = 'filament.dashboard.resources.managed-server-app-wow-resource.pages.manage-application';
    
     // Add these properties
    //protected static ?string $slug = 'managed-server-app-wows/manage';
    
    public static function getUrl(
        array $parameters = [], 
        bool $isAbsolute = true, 
        ?string $panel = null,
        ?Model $tenant = null
    ): string {
        return parent::getUrl($parameters, $isAbsolute, $panel, $tenant);
    }
    

}
