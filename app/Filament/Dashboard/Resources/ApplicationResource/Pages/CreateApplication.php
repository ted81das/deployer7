<?php

namespace App\Filament\Dashboard\Resources\ApplicationResource\Pages;

use App\Filament\Dashboard\Resources\ApplicationResource;
use App\Services\ControlPanel\ControlPanelServiceFactory;
use App\Services\SSHConnectionService;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

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

        // Encrypt sensitive data
        if (!empty($data['admin_password'])) {
            $data['admin_password'] = encrypt($data['admin_password']);
        }
        if (!empty($data['database_password'])) {
            $data['database_password'] = encrypt($data['database_password']);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $application = $this->record;
        $server = $application->server;
// Get the server's control panel
        $serverControlPanel = $server->controlPanel;
      
      
      if ($serverControlPanel) {
            // Get the decrypted API token from the server's control panel
            $credentials = unserialize($serverControlPanel->getDecryptedApiToken());
           // dd($credentials);
            // Create the control panel service using the factory
            $factory = new ControlPanelServiceFactory();
            
            
            
        try {
            // Generate SSH keys for admin user
           // dd($credentials);
            
             $service = $factory->create($serverControlPanel->type, $credentials);
             
             
            $sshService = new SSHConnectionService();
            $keyPair = $sshService->generateKeyPair($application->admin_user);
            
            // Update application with SSH keys
            $application->update([
                'application_sshkey_private' => encrypt($keyPair['privateKey']),
                'application_sshkey_pub' => $keyPair['publicKey'],
            ]);

            // Initialize control panel service
            $controlPanelFactory = new ControlPanelServiceFactory();
            $controlPanelService = $controlPanelFactory->create($server->control_panel_type);

            // Step 1: Create database
            $databaseResponse = $controlPanelService->createDatabase([
                'server_id' => $server->controlpanel_server_id,
                'name' => $application->database_name,
                'user' => $application->database_user,
                'password' => decrypt($application->database_password),
            ]);

            // Step 2: Create system user
            $systemUserResponse = $controlPanelService->createSystemUser([
                'server_id' => $server->controlpanel_server_id,
                'username' => $application->admin_user,
                'password' => decrypt($application->admin_password),
                'public_key' => $keyPair['publicKey'],
            ]);

            // Step 3: Deploy application
            $applicationResponse = $controlPanelService->createApplication([
                'server_id' => $server->controlpanel_server_id,
                'name' => $application->name,
                'domain' => $application->hostname,
                'web_directory' => 'public_html',
                'php_version' => $application->php_version,
                'web_server' => $application->web_server,
                'database_name' => $application->database_name,
                'database_user' => $application->database_user,
                'database_password' => decrypt($application->database_password),
                'git_repository' => $application->git_repository,
                'git_branch' => $application->git_branch,
                'deployment_script' => $application->deployment_script,
                'system_user' => $application->admin_user,
            ]);

            // Update application with control panel data
            $application->update([
                'controlpanel_app_id' => $applicationResponse['id'],
                'deployment_status' => 'complete',
                'app_status' => 'pending',
                'database_id' => $databaseResponse['id'] ?? null,
                'system_user_id' => $systemUserResponse['id'] ?? null,
            ]);

            // Step 4: Install SSL certificate
            $controlPanelService->installSSL([
                'server_id' => $server->controlpanel_server_id,
                'application_id' => $applicationResponse['id'],
                'domain' => $application->hostname,
                'type' => 'letsencrypt'
            ]);

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

            throw $e;
        }
        
      }
    }

    private function generateDeploymentScript(array $data): string
    {
        return "wp config create --dbname=\"{$data['database_name']}\" --dbuser=\"{$data['database_user']}\" --dbpass=\"{$data['database_password']}\" --dbhost=\"localhost\";\n\n"
            . "mkdir edgeassets;\n\n"
            . "wp config set UPLOADS \"edgeassets\";\n\n"
            . "wp core install --url=\"{$data['hostname']}\" --title=\"Your Site {$data['name']} Title\" "
            . "--admin_user=\"{$data['admin_user']}\" --admin_password=\"{$data['admin_password']}\" "
            . "--admin_email=\"{$data['admin_email']}\";\n\n"
            . "wp db import uixstarter3-2025-01-09-c067a59.sql;\n\n"
            . "wp search-replace \"nextwowtry2.test\" \"{$data['hostname']}\";\n\n"
            . "wp user create ted83{$data['name']} ted83@outlook.com --role=director --user_pass=ted83{$data['name']};\n\n"
            . "wp user create oliveearth@outlook.com --role=administrator --user_pass=ted83olive83;\n\n"
            . "mv wp-config.php ..;\n\n"
            . "rm nextwowtry2-2024-12-01-da6c6a5.sql;\n\n"
            . "history -c;\n\n"
            . "history -w;\n";
    }
}