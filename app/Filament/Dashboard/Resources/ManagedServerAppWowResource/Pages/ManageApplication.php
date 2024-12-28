<?php

namespace App\Filament\Dashboard\Resources\ManagedServerAppWowResource\Pages;

use App\Filament\Dashboard\Resources\ManagedServerAppWowResource;
use Filament\Resources\Pages\Page;

class ManageApplication extends Page
{
    protected static string $resource = ManagedServerAppWowResource::class;

    protected static string $view = 'filament.dashboard.resources.managed-server-app-wow-resource.pages.manage-application';
}
