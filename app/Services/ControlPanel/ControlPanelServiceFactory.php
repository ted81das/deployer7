<?php

namespace App\Services\ControlPanel;

class ControlPanelServiceFactory
{
    /**
     * Create a new class instance.
     */
    private const SERVICE_MAP = [
        'cloudways' => CloudwaysService::class,
        'ploi' => PloiService::class,
        'serveravatar' => ServerAvatarService::class,
        'spinupwp' => SpinupWPService::class,
        'forge' => ForgeService::class,
        'gridpane' => GridPaneService::class,
    ];

    public function create(string $type, ?string $credentials = null): ControlPanelServiceInterface
    {
        $type = strtolower($type);
        
        if (!isset(self::SERVICE_MAP[$type])) {
            throw new \Exception("Unsupported control panel type: {$type}");
        }

        $serviceClass = self::SERVICE_MAP[$type];
        
       $credentials = (string) $credentials;
        
     // Special handling for ServerAvatar
    if ($type === 'serveravatar') {
        // Since we know credentials is valid from your debug output
        return new $serviceClass($credentials);
    }

    // For other services
    return new $serviceClass($credentials, $type);
    }

        //return app($serviceClass);
}