<?php

namespace App\Filament\Dashboard\Resources\ServerResource\Pages;

use App\Filament\Dashboard\Resources\ServerResource;
use Filament\Actions;
use App\Services\SSH\SSHConnectionService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateServer extends CreateRecord
{
    protected static string $resource = ServerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate SSH Keys
        $sshService = app(SSHConnectionService::class);
        $keyPair = $sshService->generateKeyPair();

        // Generate root password if not provided (for Linode)
        $rootPassword = Str::random(12);

        return array_merge($data, [
            'uuid' => Str::uuid(),
            'provisioning_status' => 'pending',
            'server_status' => 'pending',
            'server_sshkey_public' => $keyPair['public'],
            'server_sshkey_private' => encrypt($keyPair['private']),
            'root_password' => encrypt($rootPassword),
            'user_id' => auth()->id(),
            'owner_user_id' => auth()->id(),
            'owner_email' => auth()->user()->email,
        ]);
    }

    protected function afterCreate(): void
    {
        // Get the appropriate service based on control panel type
        $controlPanel = $this->record->controlPanel;
        $serviceClass = "App\\Services\\ControlPanel\\" . ucfirst($controlPanel->type) . "Service";
        $service = app($serviceClass);

        try {
            // Transform and send request
            $serverData = $service->transformCreateServerData($this->record->toArray());
            $apiResponse = $service->createServer($serverData);

            // Update server with API response data
            $this->record->update([
                'server_ip' => $apiResponse['ip_address'] ?? null,
                'server_ipv6' => $apiResponse['ipv6_address'] ?? null,
                'controlpanel_server_id' => $apiResponse['id'] ?? null,
                'serveravatar_org_id' => $apiResponse['organization_id'] ?? null,
                'memory' => $apiResponse['memory'] ?? null,
                'cpu' => $apiResponse['cpu'] ?? null,
            ]);

        } catch (\Exception $e) {
            $this->record->update([
                'provisioning_status' => 'failed',
                'server_status' => 'failed'
            ]);
            
            throw $e;
        }
    }
}
