<?php

namespace App\Filament\Dashboard\Resources\ServerControlPanelResource\Pages;

use App\Filament\Dashboard\Resources\ServerControlPanelResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateServerControlPanel extends CreateRecord
{


protected static string $resource = ServerControlPanelResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default values
        $data['authentication_status'] = 'pending_authentication';
        $data['is_active'] = true;
        $data['user_id'] = auth()->id();

        // Set auth_type based on control panel type
        $data['auth_type'] = $data['type'] === 'cloudways' ? 'oauth2' : 'bearer';

        // Encrypt sensitive data
        if (isset($data['api_token'])) {
            $data['api_token'] = encrypt($data['api_token']);
        }
        if (isset($data['api_secret'])) {
            $data['api_secret'] = encrypt($data['api_secret']);
        }

        // Clear irrelevant fields based on type
        if ($data['type'] === 'cloudways') {
            $data['api_token'] = null;
        } else {
            $data['api_client'] = null;
            $data['api_secret'] = null;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}
