<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

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



}


