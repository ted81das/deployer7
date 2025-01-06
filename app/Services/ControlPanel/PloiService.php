<?php

namespace App\Services\ControlPanel;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Exceptions\ServerProvisioningException;

class PloiService implements ControlPanelServiceInterface
{
    protected string $apiToken;
    protected string $baseUrl = 'https://ploi.io/api';
    protected ?object $client = null;

    public function __construct(string $apiToken)
    {
        $this->apiToken = $apiToken;
    }

    public function initiateClient(): void
    {
        $this->client = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiToken}",
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ]);
    }

    public function authenticate(): bool
    {
        try {
            $response = $this->client->get("{$this->baseUrl}/servers");
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Ploi authentication failed', ['error' => $e->getMessage()]);
            return false;
        }
    }


    /*
    public function createServer(array $data): array
    {
        try {
            $serverData = $this->transformCreateServerData($data);
            $response = $this->client->post("{$this->baseUrl}/servers", $serverData);
            
            if (!$response->successful()) {
                throw new ServerProvisioningException($response->body());
            }

            return $this->transformServerResponse($response->json()['data']);
        } catch (\Exception $e) {
            Log::error('Server creation failed', ['error' => $e->getMessage()]);
            throw new ServerProvisioningException($e->getMessage());
        }
    }*/

    public function createServer(array $data): array
    {
        try {
            $serverData = $this->transformCreateServerData($data);
            $response = $this->client->post("{$this->baseUrl}/servers", $serverData);
            
            if (!$response->successful()) {
                throw new ServerProvisioningException($response->body());
            }

            $serverResponse = $response->json()['data'];

            // Get SSH keys from Ploi response
            if (isset($serverResponse['ssh_keys'])) {
                $data['server_sshkey_pub'] = $serverResponse['ssh_keys']['public_key'];
                $data['server_sshkey_private'] = encrypt($serverResponse['ssh_keys']['private_key']);
            }

            return $this->transformServerResponse($serverResponse);
        } catch (\Exception $e) {
            Log::error('Server creation failed', ['error' => $e->getMessage()]);
            throw new ServerProvisioningException($e->getMessage());
        }
    }

//method to populate server providers
public function populateServerProviders(ServerControlPanel $controlPanel): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $controlPanel->getDecryptedApiToken(),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->get('https://ploi.io/api/user/server-providers');

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch providers from Ploi');
        }

        $providers = collect($response->json())
            ->mapWithKeys(function ($provider) {
                return [$provider['id'] => $provider['name']];
            })->toArray();

        $controlPanel->update(['available_providers' => $providers]);

        return $providers;
    }








    public function transformCreateServerData(array $data): array
    {
        return [
            'name' => $data['hostname'],
            'type' => 'server',
            'plan' => $this->mapPlan($data['mapped_plan']),
            'region' => $this->mapRegion($data['mapped_region']),
            'credential' => $data['credential'] ?? config('services.ploi.default_credential'),
            'php_version' => $data['php_version'] ?? '8.2',
            'database_type' => $data['database_type'] ?? 'mysql',
            'webserver_type' => $data['webserver_type'] ?? 'nginx-docker'
        ];
    }

    public function transformServerResponse(array $response): array
    {
        return [
            'id' => $response['id'],
            'controlpanel_server_id' => (string)$response['id'],
            'server_ip' => $response['ip_address'] ?? null,
            'hostname' => $response['name'],
            'status' => $this->mapServerStatus($response['status']),
            'php_version' => $response['php_version'],
            'webserver' => $response['webserver_type'],
            'created_at' => $response['created_at']
        ];


 // Add SSH keys if available
 if (isset($response['ssh_keys'])) {
    $transformed['server_sshkey_pub'] = $response['ssh_keys']['public_key'];
    $transformed['server_sshkey_private'] = encrypt($response['ssh_keys']['private_key']);
}

    }

    public function gitDeploykeyGenerate(array $data): array
    {
        try {
            $response = $this->client->post(
                "{$this->baseUrl}/servers/{$data['server_id']}/sites/{$data['site_id']}/deployment-keys",
                ['name' => $data['key_name'] ?? 'Auto Generated Deploy Key']
            );

            if (!$response->successful()) {
                throw new \Exception('Failed to generate deployment key');
            }

            $keyData = $response->json()['data'];
            return [
                'key_id' => $keyData['id'],
                'public_key' => $keyData['public_key'],
                'fingerprint' => $keyData['fingerprint']
            ];
        } catch (\Exception $e) {
            Log::error('Deploy key generation failed', ['error' => $e->getMessage()]);
            throw new \Exception("Deploy key generation failed: {$e->getMessage()}");
        }
    }

    public function addgitDeployKeytoRepo(string $repoUrl, string $deployKey): bool
    {
        try {
            // Parse repository URL to get owner and repo name
            preg_match('/github\.com[:|\/]([^\/]+)\/([^\/\.]+)/', $repoUrl, $matches);
            if (count($matches) !== 3) {
                throw new \Exception('Invalid GitHub repository URL');
            }

            [$_, $owner, $repo] = $matches;
            
            // GitHub API call to add deploy key
            $response = Http::withHeaders([
                'Authorization' => 'token ' . config('services.github.pat_token'),
                'Accept' => 'application/vnd.github.v3+json'
            ])->post(
                "https://api.github.com/repos/{$owner}/{$repo}/keys",
                [
                    'title' => 'Ploi Deploy Key',
                    'key' => $deployKey,
                    'read_only' => true
                ]
            );

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Failed to add deploy key to GitHub', [
                'repo_url' => $repoUrl,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function showServer(string $serverId): array
    {
        try {
            $response = $this->client->get("{$this->baseUrl}/servers/{$serverId}");
            return $this->transformServerResponse($response->json()['data']);
        } catch (\Exception $e) {
            Log::error('Failed to show server', ['error' => $e->getMessage()]);
            throw new ServerProvisioningException($e->getMessage());
        }
    }

    public function deleteServer(string $serverId): bool
    {
        try {
            $response = $this->client->delete("{$this->baseUrl}/servers/{$serverId}");
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Failed to delete server', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function getServerStatus(string $serverId): string
    {
        try {
            $response = $this->client->get("{$this->baseUrl}/servers/{$serverId}");
            return $this->mapServerStatus($response->json()['data']['status']);
        } catch (\Exception $e) {
            Log::error('Failed to get server status', ['error' => $e->getMessage()]);
            throw new ServerProvisioningException($e->getMessage());
        }
    }

    public function createApplication(array $data): array
    {
        try {
            $siteData = [
                'domain' => $data['domain'],
                'project_type' => $data['application_type'],
                'directory' => $data['directory'] ?? '/public',
                'php_version' => $data['php_version'] ?? '8.2'
            ];

            $response = $this->client->post(
                "{$this->baseUrl}/servers/{$data['server_id']}/sites",
                $siteData
            );

            return $this->transformApplicationResponse($response->json()['data']);
        } catch (\Exception $e) {
            Log::error('Application creation failed', ['error' => $e->getMessage()]);
            throw new \Exception($e->getMessage());
        }
    }

    public function createDatabase(array $data): array
    {
        try {
            $dbData = [
                'name' => Str::slug($data['name'], '_'),
                'user' => Str::slug($data['name'], '_') . '_user',
                'password' => $data['password'] ?? Str::random(16)
            ];

            $response = $this->client->post(
                "{$this->baseUrl}/servers/{$data['server_id']}/databases",
                $dbData
            );

            return $this->transformDatabaseResponse($response->json()['data']);
        } catch (\Exception $e) {
            Log::error('Database creation failed', ['error' => $e->getMessage()]);
            throw new \Exception($e->getMessage());
        }
    }

    public function createSystemUser(array $data): array
    {
        try {
            $userData = [
                'name' => $data['name'],
                'username' => $data['username'],
                'password' => $data['password'] ?? Str::random(16)
            ];

            $response = $this->client->post(
                "{$this->baseUrl}/servers/{$data['server_id']}/system-users",
                $userData
            );

            return $this->transformSystemUserResponse($response->json()['data']);
        } catch (\Exception $e) {
            Log::error('System user creation failed', ['error' => $e->getMessage()]);
            throw new \Exception($e->getMessage());
        }
    }

    public function enableSSH(string $serverId, string $publicKey): bool
    {
        try {
            $response = $this->client->post(
                "{$this->baseUrl}/servers/{$serverId}/ssh-keys",
                ['public_key' => $publicKey]
            );
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('SSH key addition failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function installSSL(array $data): bool
    {
        try {
            $response = $this->client->post(
                "{$this->baseUrl}/servers/{$data['server_id']}/sites/{$data['site_id']}/certificates",
                ['type' => 'letsencrypt']
            );
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('SSL installation failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    // Helper methods
    protected function mapServerStatus(string $status): string
    {
        return match($status) {
            'creating' => 'pending',
            'running' => 'active',
            'error' => 'failed',
            default => $status
        };
    }

    protected function mapPlan(string $plan): string
    {
        return match($plan) {
            'starter' => 'g6-nanode-1',
            'advanced' => 'g6-standard-1',
            'premium' => 'g6-standard-2',
            default => 'g6-nanode-1'
        };
    }

    protected function mapRegion(string $region): string
    {
        return match($region) {
            'us-east' => 'us-east',
            'us-west' => 'us-west',
            'eu-central' => 'eu-central',
            'eu-west' => 'eu-west',
            default => 'us-east'
        };
    }

    // Additional transform methods
    protected function transformApplicationResponse(array $response): array
    {
        return [
            'id' => $response['id'],
            'domain' => $response['domain'],
            'directory' => $response['directory'],
            'project_type' => $response['project_type'],
            'status' => $response['status'],
            'created_at' => $response['created_at']
        ];
    }

    protected function transformDatabaseResponse(array $response): array
    {
        return [
            'id' => $response['id'],
            'name' => $response['name'],
            'username' => $response['username'],
            'password' => $response['password'],
            'status' => $response['status']
        ];
    }

    protected function transformSystemUserResponse(array $response): array
    {
        return [
            'id' => $response['id'],
            'name' => $response['name'],
            'username' => $response['username'],
            'status' => $response['status']
        ];
    }
}

