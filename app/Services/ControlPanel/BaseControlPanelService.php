<?php

namespace App\Services\ControlPanel;

use App\Models\ServerProvider;
use Illuminate\Support\Facades\Http;

abstract class BaseControlPanelService implements ControlPanelServiceInterface
{
    protected string $apiToken;
    protected string $baseUrl;
    protected string $type;

    public function __construct(string $apiToken, string $type)
    {
        $this->apiToken = $apiToken;
        $this->type = $type;
        $this->baseUrl = $this->getBaseUrl();
    }

    protected function getBaseUrl(): string
    {
        return match($this->type) {
            'serveravatar' => 'https://api.serveravatar.com',
            'ploi' => 'https://ploi.io/api',
            'spinupwp' => 'https://api.spinupwp.com/v1',
            'cloudways' => 'https://api.cloudways.com/api/v1',
            default => throw new \Exception("Unsupported control panel type")
        };
    }

    protected function makeRequest(string $method, string $endpoint, array $data = []): array
    {
        $headers = $this->getHeaders();
        
        $response = Http::withHeaders($headers)
            ->baseUrl($this->baseUrl)
            ->$method($endpoint, $data);

        if (!$response->successful()) {
            throw new \Exception("API request failed: " . $response->body());
        }

        return $response->json();
    }

    protected function getHeaders(): array
    {
        return match($this->type) {
            'serveravatar' => [
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Accept' => 'application/json',
            ],
            'ploi', 'spinupwp' => [
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Accept' => 'application/json',
            ],
            'cloudways' => [
                'X-Client-ID' => $this->clientId,
                'X-Client-Secret' => $this->apiToken,
                'Accept' => 'application/json',
            ],
            default => ['Accept' => 'application/json']
        };
    }

    protected function mapRegion(string $region): string 
    {
        // Mapping only Linode regions to our standardized regions
        return match($region) {
            'us-east' => [
                'serveravatar' => 'us-east', // Newark, NJ
                'ploi' => 'us-east',
                'cloudways' => 'us-east',
                'spinupwp' => 'us-east'
            ][$this->type],
            'us-central' => [
                'serveravatar' => 'us-central', // Dallas, TX
                'ploi' => 'us-central',
                'cloudways' => 'us-central',
                'spinupwp' => 'us-central'
            ][$this->type],
            'us-west' => [
                'serveravatar' => 'us-west', // Fremont, CA
                'ploi' => 'us-west',
                'cloudways' => 'us-west',
                'spinupwp' => 'us-west'
            ][$this->type],
            'eu-central' => [
                'serveravatar' => 'eu-central', // Frankfurt, DE
                'ploi' => 'eu-central',
                'cloudways' => 'eu-central',
                'spinupwp' => 'eu-central'
            ][$this->type],
            'eu-west' => [
                'serveravatar' => 'eu-west', // London, UK
                'ploi' => 'eu-west',
                'cloudways' => 'eu-west',
                'spinupwp' => 'eu-west'
            ][$this->type],
            'apac-east' => [
                'serveravatar' => 'ap-south', // Singapore
                'ploi' => 'ap-south',
                'cloudways' => 'ap-south',
                'spinupwp' => 'ap-south'
            ][$this->type],
            'apac-central' => [
                'serveravatar' => 'ap-west', // Mumbai, IN
                'ploi' => 'ap-west',
                'cloudways' => 'ap-west',
                'spinupwp' => 'ap-west'
            ][$this->type],
            default => $region
        };
    }

    protected function mapPlan(string $plan): string 
    {
        // Mapping only Linode plans
        return match($plan) {
            'starter' => [
                'serveravatar' => 'g6-nanode-1', // 1 GB RAM, 1 CPU
                'ploi' => 'linode-1gb',
                'cloudways' => 'linode-1gb',
                'spinupwp' => 'linode-1gb'
            ][$this->type],
            'advanced' => [
                'serveravatar' => 'g6-standard-2', // 4 GB RAM, 2 CPU
                'ploi' => 'linode-4gb',
                'cloudways' => 'linode-4gb',
                'spinupwp' => 'linode-4gb'
            ][$this->type],
            'premium' => [
                'serveravatar' => 'g6-standard-4', // 8 GB RAM, 4 CPU
                'ploi' => 'linode-8gb',
                'cloudways' => 'linode-8gb',
                'spinupwp' => 'linode-8gb'
            ][$this->type],
            default => $plan
        };
    }

    protected function mapProvider(string $providerId): string 
    {
        $provider = ServerProvider::find($providerId);
        
        // Only mapping Linode as provider
        return match($provider->slug) {
            'linode' => [
                'serveravatar' => 'linode',
                'ploi' => 'linode',
                'cloudways' => 'linode',
                'spinupwp' => 'linode'
            ][$this->type],
            default => throw new \Exception("Unsupported provider")
        };
    }

    protected function mapServerStatus(string $status): string 
    {
        return match(strtolower($status)) {
            'active', 'running', 'ready' => 'connected',
            'installing', 'pending', 'provisioning' => 'pending',
            'failed', 'error', 'terminated' => 'failed',
            default => 'pending'
        };
    }

    protected function mapProvisioningStatus(string $status): string 
    {
        return match(strtolower($status)) {
            'active', 'running', 'ready' => 'active',
            'installing', 'pending', 'provisioning' => 'pending',
            'failed', 'error', 'terminated' => 'failed',
            default => 'pending'
        };
    }
}
