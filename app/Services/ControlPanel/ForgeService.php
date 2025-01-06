<?php

namespace App\Services\ControlPanel;

use App\Enums\ServerProviders;
use App\Enums\ServerTypes;
use App\Enums\InstallableServices;
use App\Models\Server;
use App\Services\SSH\SSHConnectionService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ForgeService implements ControlPanelServiceInterface
{
    protected string $apiToken;
    protected string $baseUrl = 'https://forge.laravel.com/api/v1';
    protected $client;
    protected SSHConnectionService $sshService;

    public function __construct(string $apiToken, SSHConnectionService $sshService)
    {
        $this->apiToken = $apiToken;
        $this->sshService = $sshService;
    }

    public function initiateClient(): void
    {
        $this->client = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiToken}",
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ])->baseUrl($this->baseUrl);
    }

    public function authenticate(): bool
    {
        try {
            $response = $this->client->get('servers');
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Forge authentication failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function createServer(array $data): array
    {
        try {
            // Generate SSH Key Pair
            $sshKeyPair = $this->sshService->generateKeyPair();
            
            // Transform and send request
            $serverData = $this->transformCreateServerData($data);
            $serverData['ssh_key'] = $sshKeyPair['public'];
            
            $response = $this->client->post('servers', $serverData);

            if (!$response->successful()) {
                throw new ServerProvisioningException($response->body());
            }

            $serverResponse = $response->json()['server'];
            $serverResponse['server_sshkey_pub'] = $sshKeyPair['public'];
            $serverResponse['server_sshkey_private'] = encrypt($sshKeyPair['private']);

            return $this->transformServerResponse($serverResponse);
        } catch (\Exception $e) {
            Log::error('Forge server creation failed', ['error' => $e->getMessage()]);
            throw new ServerProvisioningException($e->getMessage());
        }
    }

    public function transformCreateServerData(array $data): array
    {
        return [
            'provider' => $data['provider'] ?? ServerProviders::DIGITAL_OCEAN,
            'credential_id' => $data['credential_id'] ?? 1,
            'name' => $data['hostname'],
            'type' => ServerTypes::APP,
            'size' => $this->mapPlan($data['mapped_plan']),
            'database' => $data['database'] ?? 'forge',
            'database_type' => InstallableServices::MYSQL,
            'php_version' => InstallableServices::PHP_82,
            'region' => $this->mapRegion($data['mapped_region']),
        ];
    }

    public function transformServerResponse(array $response): array
    {
        return [
            'controlpanel_server_id' => (string)$response['id'],
            'server_ip' => $response['ip_address'],
            'hostname' => $response['name'],
            'server_status' => $this->mapServerStatus($response['status']),
            'php_version' => $response['php_version'],
            'server_sshkey_pub' => $response['server_sshkey_pub'] ?? null,
            'server_sshkey_private' => $response['server_sshkey_private'] ?? null,
            'provisioning_status' => $response['is_ready'] ? 'ready' : 'pending',
        ];
    }

    public function showServer(string $serverId): array
    {
        try {
            $response = $this->client->get("servers/{$serverId}");
            return $this->transformServerResponse($response->json()['server']);
        } catch (\Exception $e) {
            Log::error('Failed to fetch server details', ['error' => $e->getMessage()]);
            throw new \Exception($e->getMessage());
        }
    }

    public function deleteServer(string $serverId): bool
    {
        try {
            $response = $this->client->delete("servers/{$serverId}");
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Failed to delete server', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function getServerStatus(string $serverId): string
    {
        try {
            $response = $this->client->get("servers/{$serverId}");
            return $this->mapServerStatus($response->json()['server']['status']);
        } catch (\Exception $e) {
            Log::error('Failed to get server status', ['error' => $e->getMessage()]);
            throw new \Exception($e->getMessage());
        }
    }

    public function createApplication(array $data): array
    {
        try {
            $applicationData = [
                'domain' => $data['domain'],
                'project_type' => $data['project_type'],
                'directory' => '/public',
                'isolation' => true
            ];

            $response = $this->client->post(
                "servers/{$data['server_id']}/sites",
                $applicationData
            );

            if ($response->successful() && isset($data['git_url'])) {
                $this->installGitRepositoryOnSite(
                    $data['server_id'],
                    $response->json()['site']['id'],
                    [
                        'provider' => 'github',
                        'repository' => $data['git_url'],
                        'branch' => $data['branch'] ?? 'main',
                        'composer' => true
                    ]
                );
            }

            return $this->transformApplicationResponse($response->json()['site']);
        } catch (\Exception $e) {
            Log::error('Application creation failed', ['error' => $e->getMessage()]);
            throw new \Exception($e->getMessage());
        }
    }

    public function createDatabase(array $data): array
    {
        try {
            $dbData = [
                'name' => $data['name'],
                'user' => $data['user'] ?? Str::slug($data['name'], '_') . '_user',
                'password' => $data['password'] ?? Str::random(16)
            ];

            $response = $this->client->post(
                "servers/{$data['server_id']}/databases",
                $dbData
            );

            return $this->transformDatabaseResponse($response->json()['database']);
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
                'password' => $data['password'] ?? Str::random(16)
            ];

            $response = $this->client->post(
                "servers/{$data['server_id']}/daemon-users",
                $userData
            );

            return $this->transformSystemUserResponse($response->json()['user']);
        } catch (\Exception $e) {
            Log::error('System user creation failed', ['error' => $e->getMessage()]);
            throw new \Exception($e->getMessage());
        }
    }

    public function enableSSH(string $serverId, string $publicKey): bool
    {
        try {
            $response = $this->client->post("servers/{$serverId}/keys", [
                'key' => $publicKey,
                'name' => 'Deploy Key'
            ]);
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
                "servers/{$data['server_id']}/sites/{$data['site_id']}/certificates/letsencrypt",
                ['domains' => [$data['domain']]]
            );
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('SSL installation failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function gitDeploykeyGenerate(array $data): array
    {
        try {
            $keyPair = $this->sshService->generateKeyPair();
            
            $response = $this->client->post(
                "servers/{$data['server_id']}/sites/{$data['site_id']}/deployment-key",
                ['key' => $keyPair['public']]
            );

            return [
                'key_id' => $response->json()['key']['id'],
                'public_key' => $keyPair['public'],
                'private_key' => encrypt($keyPair['private']),
                'fingerprint' => $this->sshService->getKeyFingerprint($keyPair['public'])
            ];
        } catch (\Exception $e) {
            Log::error('Deploy key generation failed', ['error' => $e->getMessage()]);
            throw new \Exception($e->getMessage());
        }
    }

    public function addgitDeployKeytoRepo(string $repoUrl, string $deployKey): bool
    {
        try {
            preg_match('/github\.com[:|\/]([^\/]+)\/([^\/\.]+)/', $repoUrl, $matches);
            if (count($matches) !== 3) {
                throw new \Exception('Invalid GitHub repository URL');
            }

            [$_, $owner, $repo] = $matches;
            
            $response = Http::withHeaders([
                'Authorization' => 'token ' . config('services.github.pat_token'),
                'Accept' => 'application/vnd.github.v3+json'
            ])->post(
                "https://api.github.com/repos/{$owner}/{$repo}/keys",
                [
                    'title' => 'Forge Deploy Key',
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

    protected function installGitRepositoryOnSite(string $serverId, string $siteId, array $data): bool
    {
        try {
            $response = $this->client->post(
                "servers/{$serverId}/sites/{$siteId}/git",
                $data
            );
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Git repository installation failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    protected function mapServerStatus(string $status): string
    {
        return match($status) {
            'installing' => 'pending',
            'connected' => 'active',
            'ready' => 'active',
            'failed' => 'failed',
            default => $status
        };
    }

    protected function mapPlan(string $plan): string
    {
        return match($plan) {
            'starter' => '1gb',
            'advanced' => '2gb',
            'premium' => '4gb',
            default => '1gb'
        };
    }

    protected function mapRegion(string $region): string
    {
        return match($region) {
            'us-east' => 'nyc1',
            'us-west' => 'sfo1',
            'eu-central' => 'ams2',
            'eu-west' => 'lon1',
            default => 'nyc1'
        };
    }

    protected function transformApplicationResponse(array $response): array
    {
        return [
            'id' => $response['id'],
            'name' => $response['name'],
            'domain' => $response['domain'],
            'project_type' => $response['project_type'],
            'status' => $response['status'],
            'deployment_status' => $response['deployment_status'] ?? 'pending',
            'repository' => $response['repository'] ?? null,
            'directory' => $response['directory'],
            'created_at' => $response['created_at']
        ];
    }

    protected function transformDatabaseResponse(array $response): array
    {
        return [
            'id' => $response['id'],
            'name' => $response['name'],
            'user' => $response['user'],
            'status' => $response['status']
        ];
    }

    protected function transformSystemUserResponse(array $response): array
    {
        return [
            'id' => $response['id'],
            'name' => $response['name'],
            'status' => $response['status']
        ];
    }
}

