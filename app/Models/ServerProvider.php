<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ServerProvider extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'uuid',
        'slug',
        'is_active',
        'provider_type', // DO, AWS, GCP, Vultr, Linode, etc
        'credentials_schema', // JSON schema for provider credentials
        'regions', // Available regions
        'plans', // Available plans
        'features', // Provider features
        'settings', // Provider specific settings
        'api_endpoint',
        'api_version',
        'supported_operating_systems',
        'min_server_limit',
        'max_server_limit'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'credentials_schema' => 'array',
        'regions' => 'array',
        'plans' => 'array',
        'features' => 'array',
        'settings' => 'array',
        'supported_operating_systems' => 'array',
 'uuid' => 'string' // Cast uuid as string
    ];

    const PROVIDER_DO = 'digitalocean';
    const PROVIDER_AWS = 'aws';
    const PROVIDER_VULTR = 'vultr';
    const PROVIDER_LINODE = 'linode';
    const PROVIDER_GCP = 'gcp';
    const PROVIDER_HETZNER = 'hetzner';


// Automatically generate UUID on creation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Generate a UUID for the model
            $model->uuid = (string) Str::uuid();
        });
    }

    public function servers()
    {
        return $this->hasMany(Server::class);
    }

    public function supportedControlPanels()
    {
        return $this->belongsToMany(ServerControlPanel::class, 'provider_control_panel_support')
            ->withPivot('settings')
            ->withTimestamps();
    }
}


