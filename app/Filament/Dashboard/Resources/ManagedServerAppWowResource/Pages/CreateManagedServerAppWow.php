<?php

namespace App\Filament\Dashboard\Resources\ManagedServerAppWowResource\Pages;

use App\Filament\Dashboard\Resources\ManagedServerAppWowResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use App\Services\SSHKeyService;
use App\Services\SSHConnectionService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use GuzzleHttp\Client;
use Filament\Notifications\Notification; 


class CreateManagedServerAppWow extends CreateRecord
{
    protected static string $resource = ManagedServerAppWowResource::class;
    

protected function mutateFormDataBeforeCreate(array $data): array
{
    try {
        // Validate required config values
        if (!config('services.serveravatar.org_id') || !config('services.serveravatar.server_id')) {
            throw new \Exception('ServerAvatar organization ID or server ID not configured');
        }

        // Step 1: Generate application user and system password using only alphanumeric characters
        $randomUsername = str($data['userslug'] . 'wow' . $this->generateAlphaString(8))->lower();
        $randomPassword = $this->generateAlphaNumericString(12);

        // Ensure username contains only letters and numbers
        $randomUsername = preg_replace('/[^a-zA-Z0-9]/', '', $randomUsername);

        // Step 2: Generate SSH keys using phpseclib
        $sshService = app(SSHConnectionService::class);
        $keyPair = $sshService->generateKeyPair($randomUsername);

     try {
        // Step 3: Store keys in storage
        $keyPath = "keys/{$randomUsername}";
        
        
         // Create directory if it doesn't exist
        if (!File::exists($keyPath)) {
            File::makeDirectory($keyPath, 0755, true);
        }

        // Store keys with proper paths and permissions
        $privateKeyPath = $keyPath . "/id_rsa_{$randomUsername}";
        $publicKeyPath = $keyPath . "/id_rsa_{$randomUsername}.pub";
        
        
                // Store keys with proper paths and permissions
       // $privateKeyPath = $keyPath . "/id_rsa_{$randomUsername}";
      //  $publicKeyPath = $keyPath . "/id_rsa_{$randomUsername}.pub";

        // Store using File facade for direct access to permissions
        File::put($privateKeyPath, $keyPair['privateKey']);
        File::put($publicKeyPath, $keyPair['publicKey']);

        // Set proper permissions
        chmod($privateKeyPath, 0600);  // Owner read/write only
        chmod($publicKeyPath, 0644);   // Owner read/write, others read
        chmod($keyPath, 0755);         // Directory permissions

        // Verify storage
        if (!File::exists($privateKeyPath) || !File::exists($publicKeyPath)) {
            throw new \Exception('Failed to store SSH keys');}
        
        
     } catch (\Exception $e) {
        \Log::error('SSH key storage failed: ' . $e->getMessage());
        throw new \Exception('Failed to store SSH keys: ' . $e->getMessage());
    }   
        //Storage::disk('local')->put("{$keyPath}/id_rsa_{$randomUsername}", $keyPair['privateKey']);
        //Storage::disk('local')->put("{$keyPath}/id_rsa_{$randomUsername}.pub", $keyPair['publicKey']);









        // Step 4: Create System User via ServerAvatar API
        $client = new Client();
        $systemUserResponse = $client->post(
            config('services.serveravatar.api_url') . '/organizations/' . 
            config('services.serveravatar.org_id') . '/servers/' . 
            config('services.serveravatar.server_id') . '/system-users',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . config('services.serveravatar.token'),
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'username' => $randomUsername,
                    'password' => $randomPassword,
                    'password_confirmation' => $randomPassword,
                    'public_key' => $keyPair['publicKey']
                ]
            ]
        );

        $systemUserData = json_decode($systemUserResponse->getBody(), true);
        
         // Notification for system user creation
        Notification::make()
            ->title('System User Created')
            ->success()
            ->body("User {$randomUsername} created successfully.")
            ->send();


        // Generate database credentials with only alphanumeric characters
        $dbName = str($data['userslug'] . 'wow' . $this->generateAlphaString(8))->lower();
        $dbUsername = str($data['userslug'] . 'wow' . $this->generateAlphaString(8))->lower();
        $dbPassword = $this->generateAlphaNumericString(12);

        // Clean up any remaining special characters
        $dbName = preg_replace('/[^a-zA-Z0-9]/', '', $dbName);
        $dbUsername = preg_replace('/[^a-zA-Z0-9]/', '', $dbUsername);



        // Step 5: Create Database via ServerAvatar API
        try {
            $databaseResponse = $client->post(
                config('services.serveravatar.api_url') . '/organizations/' . 
                config('services.serveravatar.org_id') . '/servers/' . 
                config('services.serveravatar.server_id') . '/databases',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . config('services.serveravatar.token'),
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json'
                    ],
                    'json' => [
                        'name' => $dbName,
                        'username' => $dbUsername,
                        'password' => $dbPassword,
                        'password_confirmation' => $dbPassword
                    ]
                ]
            );

            $databaseData = json_decode($databaseResponse->getBody(), true);
            
            // Log successful database creation
            \Log::info('Database created successfully', [
                'database' => $dbName,
                'username' => $dbUsername
            ]);

        } catch (\Exception $e) {
            \Log::error('Database creation failed: ' . $e->getMessage());
            throw new \Exception('Failed to create database: ' . $e->getMessage());
        }






$privateKey1 = str_replace('"""', '', $keyPair['privateKey']); // Remove triple quotes if present
$privateKey1 = stripslashes($privateKey1); // Remove escape characters
$privateKey1 = preg_replace('/\\\\n/', "\n", $privateKey1); // Convert \n to actual newlines

        // Prepare form data
        $formData = [
            // Original form data
            'userslug' => $data['userslug'],
            'application_name' => $data['application_name'],
            'app_hostname' => $data['app_hostname'],
            'app_miniadmin_username' => $data['app_miniadmin_username'],
            'app_miniadmin_email' => $data['app_miniadmin_email'],
            'app_miniadmin_password' => Crypt::encryptString($data['app_miniadmin_password']),
            'clone_url' => $data['clone_url'],
            'branch' => $data['branch'] ?? 'main',
            'php_version' => $data['php_version'],

            // Server and system user data
            'managed_server_id' => config('services.serveravatar.server_id'),
            'application_user' => $randomUsername,
            'system_password' => Crypt::encryptString($randomPassword),
            'application_user_id' => $systemUserData['systemUser']['id'],
            'system_user_info' => json_encode($systemUserData['systemUser']),

 // Store encrypted private key and public key in database
                'application_sshkey_private' => Crypt::encryptString($privateKey1),
                'application_sshkey_pub' => $keyPair['publicKey'],


            // Database credentials
            'db_name' => $dbName,
            'db_username' => $dbUsername,
            'db_password' => Crypt::encryptString($dbPassword),

            // Default values
            'webroot' => '',
            'git_provider_id' => config('services.serveravatar.git_provider_id'),
            'phpseclib_connection_status' => false,
        ];

        // Step 5: Deploy application via ServerAvatar API
        $applicationResponse = $client->post(
            config('services.serveravatar.api_url') . '/organizations/' . 
            config('services.serveravatar.org_id') . '/servers/' . 
            config('services.serveravatar.server_id') . '/applications',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . config('services.serveravatar.token'),
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'name' => $formData['application_name'],
                    'method' => 'git',
                    'framework' => 'github',
                    'hostname' => $formData['app_hostname'],
                    'systemUser' => 'existing',
                    'systemUserId' => $formData['application_user_id'],
                    'php_version' => $formData['php_version'],
                    'webroot' => '',
                    'www' => false,
                    'type' => 'public',
                    'temp_sub_domain' => false,
                      'temp_domain' => false,
                    'temp_sub_domain_name' => $formData['application_name'],
                    'temp_domain_url' =>config('services.serveravatar.temp_domain_url', 'satemp.site'),
                    'git_provider_id' => config('services.serveravatar.git_provider_id'),
                    'clone_url' => $formData['clone_url'],
                    'branch' => $formData['branch'],
                    'script' => $this->generateWpCliScript($formData)
                ]
            ]
        );

        $applicationData = json_decode($applicationResponse->getBody(), true);
        
        // Merge the application ID into form data
        $formData['serveravatar_application_id'] = $applicationData['application']['id'] ?? null;
        
         // Notification for application deployment
        Notification::make()
            ->title('Application Deployed')
            ->success()
            ->body("Application {$formData['application_name']} deployed successfully.")
            ->send();
        
        //ADD SSL AND SSH TOGGLE 
        
        // Step 6: Install SSL Certificate
$sslResponse = $client->post(
    config('services.serveravatar.api_url') . '/organizations/' . 
    config('services.serveravatar.org_id') . '/servers/' . 
    config('services.serveravatar.server_id') . '/applications/' . 
    $formData['serveravatar_application_id'] . '/ssl',
    [
        'headers' => [
            'Authorization' => 'Bearer ' . config('services.serveravatar.token'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ],
        'json' => [
            'ssl_type' => 'automatic',
            'force_https' => false
        ]
    ]
);

$sslData = json_decode($sslResponse->getBody(), true);
$formData['ssl_status'] = $sslData['status'] ?? 'pending';

 // Notification for SSL installation
            Notification::make()
                ->title('SSL Certificate Installed')
                ->success()
                ->body($sslData['message'] ?? 'SSL certificate installed successfully.')
                ->send();

// Step 7: Toggle SSH Access for Application User
$sshAccessResponse = $client->get(
    config('services.serveravatar.api_url') . '/organizations/' . 
    config('services.serveravatar.org_id') . '/servers/' . 
    config('services.serveravatar.server_id') . '/system-users/' . 
    $formData['application_user_id'] . '/ssh-access',
    [
        'headers' => [
            'Authorization' => 'Bearer ' . config('services.serveravatar.token'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ]
    ]
);

$sshAccessData = json_decode($sshAccessResponse->getBody(), true);
$formData['ssh_access_status'] = $sshAccessData['status'] ?? 'unknown';
        // dd($keyPair['privateKey'],$keyPair['publicKey']);

        return $formData;

    } catch (\Exception $e) {
        \Log::error('Application creation failed: ' . $e->getMessage());
        throw new \Exception('Failed to create application: ' . $e->getMessage());
    }
}


private function generateWpCliScript(array $data): string
{
    // Decrypt the passwords since they're stored encrypted
    $dbPassword = Crypt::decryptString($data['db_password']);
    $adminPassword = Crypt::decryptString($data['app_miniadmin_password']);

    return implode("\n", [
        // Create wp-config.php with generated database credentials
        "wp config create --dbname=\"{$data['db_name']}\" --dbuser=\"{$data['db_username']}\" --dbpass=\"{$dbPassword}\" --dbhost=\"localhost\";",
        
        // Install WordPress with provided admin credentials
        "wp core install --url=\"{$data['app_hostname']}\" --title=\"{$data['application_name']}\" --admin_user=\"{$data['app_miniadmin_username']}\" --admin_password=\"{$adminPassword}\" --admin_email=\"{$data['app_miniadmin_email']}\";",
        
        // Import base database
        "wp db import nextwowtry2-2024-12-01-da6c6a5.sql;",
        
        // Update URLs in database
        "wp search-replace \"nextwowtry2.test\" \"{$data['app_hostname']}\";",
        
        // Optional: Clear cache and optimize database
        "wp cache flush;",
        "wp db optimize;"
    ]);
}



/*private function generateWpCliScript(array $data): string

{
    return implode("\n", [
        "wp config create --dbname=\"{$data['db_name']}\" --dbuser=\"{$data['db_username']}\" --dbpass=\"" . Crypt::decryptString($data['db_password']) . "\" --dbhost=\"localhost\";",
        "wp core install --url=\"{$data['app_hostname']}\" --title=\"{$data['application_name']}\" --admin_user=\"{$data['app_miniadmin_username']}\" --admin_password=\"" . $data['app_miniadmin_password'] . "\" --admin_email=\"{$data['app_miniadmin_email']}\";",
        "wp db import nextwowtry2-2024-12-01-da6c6a5.sql;",
        "wp search-replace \"nextwowtry2.test\" \"{$data['app_hostname']}\";"
    ]);
}
*/
/**
 * Generate a random string containing only letters
 */
private function generateAlphaString(int $length): string
{
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';
    
    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $string;
}

/**
 * Generate a random string containing only letters and numbers
 */
private function generateAlphaNumericString(int $length): string
{
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $string = '';
    
    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $string;
}


protected function afterCreate(): void 
    {
        // Test initial connection
       
        $this->record->updateConnectionStatus();
    }
}
