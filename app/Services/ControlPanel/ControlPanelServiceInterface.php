<?php

namespace App\Services\ControlPanel;
use App\Models\ServerControlPanel;  // Add this import

interface ControlPanelServiceInterface
{
    public function initiateClient();
    public function authenticate(): bool;
    public function createServer(array $data): array;
    public function showServer(string $serverId): array;
    public function deleteServer(string $serverId): bool;
    public function getServerStatus(string $serverId): string;
    public function createApplication(array $data): array;
    public function createDatabase(array $data): array;
    public function createSystemUser(array $data): array;
    public function enableSSH(string $serverId, string $publicKey): bool;
    public function installSSL(array $data): bool;
    public function transformCreateServerData(array $data): array;
    public function transformServerResponse(array $response): array;
    public function gitDeploykeyGenerate(array $data): array;
    public function addgitDeployKeytoRepo(string $repoUrl, string $deployKey): bool;
 //add refresh ServerProviders list   
 public function populateServerProviders(ServerControlPanel $controlPanel): array;
}
