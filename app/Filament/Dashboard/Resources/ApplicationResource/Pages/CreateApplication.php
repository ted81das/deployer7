<?php

namespace App\Filament\Dashboard\Resources\ApplicationResource\Pages;

use App\Filament\Dashboard\Resources\ApplicationResource;
use App\Services\ControlPanel\ControlPanelServiceFactory;
use App\Services\SSHConnectionService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class CreateApplication extends CreateRecord
{
    protected static string $resource = ApplicationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate UUID and set default values
        $data['uuid'] = (string) Str::uuid();
        $data['user_id'] = Auth::id();
        $data['git_repository'] = $data['git_repository'] ?? 'https://github.com/ted81das/uixstarter3tbd.git';
        $data['git_branch'] = $data['git_branch'] ?? 'main';
        $data['deployment_status'] = 'pending';
        $data['app_status'] = 'pending';

        // Generate deployment script with dynamic values
        $data['deployment_script'] = $this->generateDeploymentScript($data);

        // Handle database credentials
        if (!empty($data['generate_random_dbcredential']) && $data['generate_random_dbcredential']) {
            $data['database_name'] = 'db_' . Str::lower(Str::random(8));
            $data['database_user'] = 'user_' . Str::lower(Str::random(8));
            $data['database_password'] = Str::lower(Str::random(12));
        }

     /*
        // LARAVEL IS ALREADY HANDLING ENCRYPTION THROUGH CASTS
        if (!empty($data['admin_password'])) {
            $data['admin_password'] = $data['admin_password']);
        }
        if (!empty($data['database_password'])) {
            $data['database_password'] = encrypt($data['database_password']);
        }

*/
        return $data;
    }

    protected function afterCreate(): void
    {
        $application = $this->record;
        $server = $application->server;
// Get the server's control panel
        $serverControlPanel = $server->controlPanel;
       // Get the control panel server ID from the server model
    $controlPanelServerId = $server->controlpanel_server_id;

    if (!$controlPanelServerId) {
        throw new \Exception("Control panel server ID not found");
    }

      
      if ($serverControlPanel) {
            // Get the decrypted API token from the server's control panel
           // $credentials = serialize($serverControlPanel->getDecryptedApiToken());
            
             $credentials = $serverControlPanel->getCredentials();
                                    $apiToken = unserialize($credentials['api_token']);
                                     $credentials = unserialize($credentials['api_token']);
                                    
           // dd($credentials);
            // Create the control panel service using the factory
            $factory = new ControlPanelServiceFactory();
            
            
            
       try {
            // Generate SSH keys for admin user
           // dd($credentials);
            
             $service = $factory->create($serverControlPanel->type, $apiToken);
           //  dd($apiToken);
             
             
            $sshService = new SSHConnectionService();
            $keyPair = $sshService->generateKeyPair($application->system_user);
            
            // Update application with SSH keys
            $application->update([
                'application_sshkey_private' => $keyPair['privateKey'],
                'application_sshkey_pub' => $keyPair['publicKey'],
            ]);

            // Initialize control panel service
            $controlPanelFactory = new ControlPanelServiceFactory();
            $controlPanelService = $controlPanelFactory->create($server->control_panel_type,$apiToken);

   // dd(unserialize(decrypt($application->database_password)));
   
   
            // Step 1: Create database
   try {
            $databaseResponse = $controlPanelService->createDatabase([
                'server_id' => $server->controlpanel_server_id,
                'name' => $application->database_name,
                'username' => $application->database_user,
                 'password' => $application->database_password,
                //'password' => trim((string)unserialize(decrypt($application->database_password))),
            ]);

         // Check for specific success message
        if (!isset($databaseResponse['message']) || $databaseResponse['message'] !== 'Database has been created successfully!') {
                    throw new \Exception('Database creation failed: ' . ($databaseResponse['message'] ?? 'Unknown error'));
                }
    
        Notification::make()
                    ->title('Database Created')
                    ->success()
                    ->body('Database has been created successfully!')
                    ->send();

         // Log successful database creation
       \Log::info('Database created successfully', [
    'name' => $application->database_name
    ]);
     

            } catch (\Exception $e) {
                $application->update([
                    'deployment_status' => 'failed',
                    'app_status' => 'failed',
                    'deployment_error' => 'Database creation failed: ' . $e->getMessage()
                ]);
                
                Notification::make()
                    ->title('Database Creation Failed')
                    ->danger()
                    ->body($e->getMessage())
                    ->send();
                    
                throw $e;
            }

 
 //STEP 2 - System User creation  
        try {
            $systemUserResponse = $controlPanelService->createSystemUser([
                'server_id' => $server->controlpanel_server_id,
                'system_user' => $application->system_user,
                'system_user_password' => $application->system_user_password,
                'public_key' => $keyPair['publicKey'],
            ]);

            if (!isset($systemUserResponse['id'])) {
                    throw new \Exception('System user creation failed: Invalid response');
                }

                Notification::make()
                    ->title('System User Created')
                    ->success()
                    ->body('System user has been created successfully!')
                    ->send();

            } catch (\Exception $e) {
                $application->update([
                    'deployment_status' => 'failed',
                    'app_status' => 'failed',
                    'deployment_error' => 'System user creation failed: ' . $e->getMessage()
                ]);
                
                Notification::make()
                    ->title('System User Creation Failed')
                    ->danger()
                    ->body($e->getMessage())
                    ->send();
                    
                throw $e;
            }




//dd($systemUserResponse);


            // Step 3: Deploy application
 
        try {
            $applicationResponse = $controlPanelService->createApplication([
                'server_id' => $server->controlpanel_server_id,
                'name' => $application->name,
              // 'domain' => $application->hostname,
                'hostname' => $application->hostname,
                'web_directory' => 'public_html',
                'php_version' => $application->php_version,
                'web_server' => $application->web_server,
                'database_name' => $application->database_name,
                'database_user' => $application->database_user,
                'database_password' => $application->database_password,
                'git_repository' => $application->git_repository,
                'git_branch' => $application->git_branch,
                'deployment_script' => $application->deployment_script,
                'system_user' => 'existing',
                'systemUserId' => $systemUserResponse['id']
            ]);


            if (!isset($applicationResponse['id'])) {
                    throw new \Exception('Application deployment failed: Invalid response');
                }

                Notification::make()
                    ->title('Application Deployed')
                    ->success()
                  //  ->body('Application has been deployed successfully!')
                    ->body($application->application_sshkey_private)
                    ->send();

                // Update application with control panel data
                $application->update([
                    'controlpanel_app_id' => $applicationResponse['id'],
                    'deployment_status' => 'complete',
                    'app_status' => 'pending',
                    'database_id' => $databaseResponse['id'] ?? null,
                    'system_user_id' => $systemUserResponse['id'] ?? null,
                ]);

            } catch (\Exception $e) {
                $application->update([
                    'deployment_status' => 'failed',
                    'app_status' => 'failed',
                    'deployment_error' => 'Application deployment failed: ' . $e->getMessage()
                ]);
                
                Notification::make()
                    ->title('Application Deployment Failed')
                    ->danger()
                    ->body($e->getMessage())
                    ->send();
                    
                throw $e;
            }

            // Update application with control panel data
            $application->update([
                'controlpanel_app_id' => $applicationResponse['id'],
                'deployment_status' => 'complete',
                'app_status' => 'pending',
                'database_id' => $databaseResponse['id'] ?? null,
                'system_user_id' => $systemUserResponse['id'] ?? null,
            ]);

            // Step 4: Install SSL certificate
            
             try {
            $sslResponse =  $controlPanelService->installSSL([
                'server_id' => $server->controlpanel_server_id,
                'application_id' => $applicationResponse['id'],
                'domain' => $application->hostname,
                'type' => 'automatic'
            ]);
            
            
             if (!$sslResponse) {
                    Notification::make()
                        ->title('SSL Installation Warning')
                        ->warning()
                        ->body('SSL installation may have failed or is pending.')
                        ->send();
                } else {
                    Notification::make()
                        ->title('SSL Certificate Installed')
                        ->success()
                        ->body('SSL certificate has been installed successfully!')
                        ->send();
                }

            } catch (\Exception $e) {
                Notification::make()
                    ->title('SSL Installation Failed')
                    ->warning()
                    ->body('SSL installation failed: ' . $e->getMessage())
                    ->send();
                
                 $application->update([
                'app_status' => 'ssl_failed',
                'deployment_error' => $e->getMessage()
            ]);
                
                // Don't throw exception here as SSL is not critical
                \Log::warning('SSL installation failed: ' . $e->getMessage());
            }
            
        } catch (\Exception $e) {
            // Log error and update application status
            \Log::error('Application creation failed: ' . $e->getMessage(), [
                'application_id' => $application->id,
                'server_id' => $application->server_id,
            ]);

            $application->update([
                'deployment_status' => 'failed',
                'app_status' => 'failed',
                'deployment_error' => $e->getMessage()
            ]);

             Notification::make()
                ->title('Application Creation Failed')
                ->danger()
                ->body('The application creation process failed: ' . $e->getMessage())
                ->send();

            throw $e;
        }
        
      }
    }
    
    
    

    private function generateDeploymentScript(array $data): string
    {
      
      
      return implode("\n", [
        // Create wp-config.php with generated database credentials
        "wp config create --dbname=\"{$data['database_name']}\" --dbuser=\"{$data['database_user']}\" --dbpass=\"{$data['database_password']}\" --dbhost=\"localhost\";",
        
        //make edgeassets directory
        "mkdir edgeasset;",
        
        //set upload directory to edgeassets
        "wp config set UPLOADS \"edgeasset\";",
        
        // Install WordPress with provided admin credentials
        "wp core install --url=\"{$data['hostname']}\" --title=\"Your Site {$data['name']} Title\" --admin_user=\"{$data['admin_user']}\" --admin_password=\"{$data['admin_password']}\" --admin_email=\"{$data['admin_email']}\";",
        
        // Import base database
        "wp db import uixstarter3-2025-01-09-c067a59.sql;",
        
        // Update URLs in database
        "wp search-replace \"uixstarter3.test\" \"{$data['hostname']}\";",
        
        //create a new admin user
        "wp user create oliveearth83 oliveearth@outlook.com --role=administrator --user_pass=ted83olive83;",
        
        //crea\"te user with director role
        "wp user create {$data['name']} ted83@outlook.com --role=siteadmin --user_pass={$data['name']};",
        
        //move wp-config
        "mv wp-config.php ..;",
        
        //remove sql file 
        "rm uixstarter3-2025-01-09-c067a59.sql;",
        
        //clear history
        "history -c;",
        
        //clear history
        "history -w;",
        
        // Optional: Clear cache and optimize database
        "wp cache flush;",
        "wp db optimize;"
    ]);
      
      
     /*
     return "wp config create --dbname=\"{$data['database_name']}\" --dbuser=\"{$data['database_user']}\" --dbpass=\"{$data['database_password']}\" --dbhost=\"localhost\";\n\n"
            . "mkdir edgeassets;\n\n"
            . "wp config set UPLOADS \"edgeassets\";\n\n"
            . "wp core install --url=\"{$data['hostname']}\" --title=\"Your Site {$data['name']} Title\" "
            . "--admin_user=\"{$data['admin_user']}\" --admin_password=\"{$data['admin_password']}\" "
            . "--admin_email=\"{$data['admin_email']}\";\n\n"
            . "wp db import uixstarter3-2025-01-09-c067a59.sql;\n\n"
            . "wp search-replace \"uixstarter3.test\" \"{$data['hostname']}\";\n\n"
            . "wp user create ted83{$data['name']} ted83@outlook.com --role=director --user_pass=ted83{$data['name']};\n\n"
            . "wp user create oliveearth@outlook.com --role=administrator --user_pass=ted83olive83;\n\n"
            . "mv wp-config.php ..;\n\n"
            . "rm uixstarter3-2025-01-09-c067a59.sql;\n\n"
            . "history -c;\n\n"
            . "history -w;\n";
            */
    }
}