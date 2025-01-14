<?php

namespace App\Filament\Admin\Resources\GitProviderResource\Pages;

use App\Filament\Admin\Resources\GitProviderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateGitProvider extends CreateRecord
{
    protected static string $resource = GitProviderResource::class;
}
