<?php

namespace App\Filament\Dashboard\Resources\ServerResource\Pages;

use App\Filament\Dashboard\Resources\ServerResource;
use Filament\Actions;
use App\Services\SSHConnectionService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use App\Models\ServerControlPanel;
use App\Services\ControlPanel\ControlPanelServiceFactory;

class CreateServer extends CreateRecord
{
    protected static string $resource = ServerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate SSH Keys
        $sshService = app(SSHConnectionService::class);
        $keyPair = $sshService->generateKeyPair('root');

        // Generate root password if not provided (for Linode)
        $rootPassword = $this->generateSecurePassword();
         $providerId = $data['provider_id'] ?? null;
       // dd($rootPassword);

        return array_merge($data, [
            'uuid' => Str::uuid(),
            'hostname' => $data['hostname'], 
            'provisioning_status' => 'pending',
            'server_status' => 'pending',
            'server_sshkey_public' => $keyPair['publicKey'],
            'server_sshkey_private' => encrypt($keyPair['privateKey']),
            'root_password' => encrypt($rootPassword),
            'linode_root_password' => $rootPassword, 
            'user_id' => auth()->id(),
            'owner_user_id' => auth()->id(),
            'owner_email' => auth()->user()->email,
            'provider_id' => $providerId, 
            'web_server' => $data['web_server'],
            'database_type' => $data['database_type'],
            'region' => $data['region'],
            'plan' => $data['plan']
        ]);
    }

   
   //changed afterCreate method to instantiate using ControlPanelServiceFactory
   protected function afterCreate(): void
{
    // Get the appropriate service based on control panel type
    $controlPanel = $this->record->controlPanel;
    
    // Use the factory to create the service instance
    $serviceFactory = app(ControlPanelServiceFactory::class);
    $credentials = $controlPanel->getCredentials(); // Make sure this returns an array
    $apiToken = unserialize($credentials['api_token']);
    $service = $serviceFactory->create($controlPanel->type, $apiToken);
    
    
   


  //  $service = $serviceFactory->create($controlPanel->type, $controlPanel->getDecryptedApiToken());

    try {
      
       // Get the record data as array
        $serverData = $this->record->toArray();
      //  dd($serverData);
      //  $serverData['hostname'] = $this->record->hostname;
       // Add the decrypted root password to the data array
       // $serverData['linode_root_password'] = $this->generateSecurePassword(); // Use the same password generated in mutateFormDataBeforeCreate
        $serverData['linode_root_password'] = "Sanfrancisco@202!2024";
        $storerootPassword = $serverData['linode_root_password'];
        // Transform and send request
      // dd($serverData);
        $serverData = $service->transformCreateServerData($serverData);
       // dd($serverData);
        $apiResponse = $service->createServer($serverData);
        // Transform and send request
       /* $serverData = $service->transformCreateServerData($this->record->toArray());
        $apiResponse = $service->createServer($serverData);*/

        // Update server with API response data
        $this->record->update([
            'server_ip' => $apiResponse['ip_address'] ?? null,
            'server_ipv6' => $apiResponse['ipv6_address'] ?? null,
            'controlpanel_server_id' => $apiResponse['id'] ?? null,
            'serveravatar_org_id' => $apiResponse['organization_id'] ?? null,
            'root_password' => $serverData['linode_root_password'],
           // 'serveravatar_org_id' => $apiResponse['organization_id'] ?? null,
            'memory' => $apiResponse['memory'] ?? null,
            'cpu' => $apiResponse['cpu'] ?? null,
            'provisioning_status' => 'provisioning',
        ]);

    } catch (\Exception $e) {
        $this->record->update([
            'provisioning_status' => 'failed',
            'server_status' => 'failed'
        ]);
        
        throw $e;
    }
}
   
   
   
   
   
   /*
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
    } */



protected function generateSecurePassword(int $length = 12): string
{
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $numbers = '0123456789';
    $special = '!@#$%^&*()_+-=[]{}|;:,.<>?';

    // Ensure we have at least one of each required character type
    $password = [
        $uppercase[random_int(0, strlen($uppercase) - 1)], // one uppercase
        $numbers[random_int(0, strlen($numbers) - 1)],     // one number
        $special[random_int(0, strlen($special) - 1)],     // one special char
    ];

    // Fill the rest with random characters from all possible characters
    $allChars = $uppercase . $lowercase . $numbers . $special;
    for ($i = count($password); $i < $length; $i++) {
        $password[] = $allChars[random_int(0, strlen($allChars) - 1)];
    }

    // Shuffle the password array to make it random
    shuffle($password);

    return implode('', $password);
}


}