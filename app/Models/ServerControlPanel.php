<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Crypt;

class ServerControlPanel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'uuid',
        'type',
        'authentication_status',
        'available_providers',
        'api_client',
        'api_secret',
        'api_token',
        'base_url',
        'auth_type',
        'settings',
        'meta_data',
        'is_active',
        'last_authenticated_at',
        'authentication_error',
        'supported_php_versions',
        'supported_web_servers',
        'supported_databases',
        'supported_features',
        'rate_limits',
        'user_id'
    ];

    protected $casts = [
        'available_providers' => 'array',
        'settings' => 'encrypted:array',
        'meta_data' => 'array',
        'is_active' => 'boolean',
        'last_authenticated_at' => 'datetime',
        'supported_php_versions' => 'array',
        'supported_web_servers' => 'array',
        'supported_databases' => 'array',
        'supported_features' => 'array',
        'rate_limits' => 'array',
        'api_secret' => 'encrypted',
        'api_token' => 'encrypted'
    ];

    const TYPE_SERVERAVATAR = 'serveravatar';
    const TYPE_CLOUDWAYS = 'cloudways';
    const TYPE_PLOI = 'ploi';
    const TYPE_SPINUPWP = 'spinupwp';
    const TYPE_FORGE = 'forge';

    const STATUS_PENDING = 'pending_authentication';
    const STATUS_AUTHENTICATED = 'authenticated';
    const STATUS_FAILED = 'failed';


/*
 protected $enums = [
        'type' => ['serveravatar', 'cloudways', 'ploi', 'spinupwp', 'forge'],
        'authentication_status' => ['pending_authentication', 'authenticated', 'failed'],
        'auth_type' => ['bearer', 'oauth2', 'basic']
    ];

*/

protected static function boot()
{
    parent::boot();

    static::creating(function ($model) {
        if (empty($model->uuid)) {
            $model->uuid = (string) Str::uuid();
        }
    });
}





    public function servers()
    {
        return $this->hasMany(Server::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function supportedProviders()
    {
        return $this->belongsToMany(ServerProvider::class, 'provider_control_panel_support')
            ->withPivot('settings')
            ->withTimestamps();
    }

    public function supportedApplicationTypes()
    {
        return $this->belongsToMany(ApplicationType::class, 'control_panel_application_types')
            ->withPivot('settings')
            ->withTimestamps();
    }





    /**
     * Get the decrypted API token
     */



    /**
     * Get the decrypted API token
     */
    public function getDecryptedApiToken(): ?string
    {
        try {
            if (empty($this->api_token)) {
                return null;
            }
            return Crypt::decryptString($this->api_token);
        } catch (\Exception $e) {
            return $this->api_token; // Return raw value if decryption fails
        }
    }

    /**
     * Set the API token
     */
    public function setApiTokenAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['api_token'] = Crypt::encryptString($value);
        }
    }

    /**
     * Get the decrypted API secret
     */
    public function getDecryptedApiSecret(): ?string
    {
        try {
            if (empty($this->api_secret)) {
                return null;
            }
            return Crypt::decryptString($this->api_secret);
        } catch (\Exception $e) {
            return $this->api_secret; // Return raw value if decryption fails
        }
    }

    /**
     * Set the API secret
     */
    public function setApiSecretAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['api_secret'] = Crypt::encryptString($value);
        }
    }

    /**
     * Get credentials based on control panel type
     */
    public function getCredentials(): array
    {
        if ($this->type === 'cloudways') {
            return [
                'api_client' => $this->api_client,
                'api_secret' => $this->getDecryptedApiSecret(),
            ];
        }

        return [
            'api_token' => $this->getDecryptedApiToken(),
        ];
    }



/*

    public function getDecryptedApiToken(): ?string

    {
        if ($this->type === 'cloudways') {
            return null; // Cloudways uses api_client and api_secret instead
        }
        
        return $this->api_token ? decrypt($this->getRawOriginal('api_token')) : null;
    }

    
    // Get the decrypted API secret (for Cloudways)
     
  
  public function getDecryptedApiSecret(): ?string
    {
        if ($this->type !== 'cloudways') {
            return null;
        }
        
        return $this->api_secret ? decrypt($this->getRawOriginal('api_secret')) : null;
    }

    
     // Get credentials based on control panel type
     
    public function getCredentials(): array
    {
        if ($this->type === 'cloudways') {
            return [
                'api_client' => $this->api_client,
                'api_secret' => $this->getDecryptedApiSecret(),
            ];
        }

        return [
            'api_token' => $this->getDecryptedApiToken(),
        ];
    }

*/

   public function authenticate()
    {
        try {
            $service = $this->getControlPanelService();
            $result = $service->authenticate($this->api_token);
            
            $this->update([
                'authentication_status' => self::STATUS_AUTHENTICATED,
                'last_authenticated_at' => now(),
                'auth_error_message' => null
            ]);

            return true;
        } catch (\Exception $e) {
            $this->update([
                'authentication_status' => self::STATUS_FAILED_AUTHENTICATION,
                'auth_error_message' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    public function populateProviders()
    {
        if ($this->authentication_status !== self::STATUS_AUTHENTICATED) {
            throw new \Exception('Control panel not authenticated');
        }

        try {
            $service = $this->getControlPanelService();
            $providers = $service->getAvailableProviders();
            
            $this->update([
                'available_providers' => $providers
            ]);

            return $providers;
        } catch (\Exception $e) {
            throw new \Exception('Failed to fetch providers: ' . $e->getMessage());
        }
    }


public function getControlPanelService()
{
    $serviceMap = [
        'cloudways' => \App\Services\ControlPanel\CloudwaysService::class,
        'forge' => \App\Services\ControlPanel\ForgeService::class,
        'gridpane' => \App\Services\ControlPanel\GridPaneService::class,
        'ploi' => \App\Services\ControlPanel\PloiService::class,
        'serveravatar' => \App\Services\ControlPanel\ServerAvatarService::class,
        'spinupwp' => \App\Services\ControlPanel\SpinupWPService::class,
    ];

    if (!isset($serviceMap[$this->type])) {
        throw new \Exception("Unsupported control panel type: {$this->type}");
    }

    // Get the service class
    $serviceClass = $serviceMap[$this->type];

    // Initialize the service with appropriate parameters based on type
    return match($this->type) {
        'serveravatar' => new $serviceClass(
            $this->api_token,
            $this->organization_id
        ),
        'ploi' => new $serviceClass(
            $this->api_token
        ),
        'cloudways', 'forge', 'gridpane', 'spinupwp' => new $serviceClass(
            $this->api_token,
            $this->type
        ),
        default => throw new \Exception("Unsupported control panel type")
    };
}






}


