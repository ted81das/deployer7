<?php

namespace App\Filament\Admin\Resources\ApplicationTypeResource\Pages;

use App\Filament\Admin\Resources\ApplicationTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateApplicationType extends CreateRecord
{
    protected static string $resource = ApplicationTypeResource::class;
}
