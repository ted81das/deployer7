<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicationType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'description',
        'is_active',
        'is_global',
        'is_git',
        'is_git_supported',
        'git_deployment_url',
        'file_name',
        'git_provider_id',
        'repository',
        'username',
        'repository_name',
        'branch',
        'default_branch',
        'deployment_script_template',
        'deployment_script',
        'post_deployment_script',
        'environment_template',
        'required_php_extensions',
        'required_dependencies',
        'minimum_php_version',
        'recommended_php_version',
        'supported_databases',
        'default_web_server',
        'configuration_options',
        'allowed_web_server_types',
        'user_id',
        'icon_path',
        'documentation_url',
        'template_id',
        'cloudpanel_curl',
        'is_cloud_curl_script',
        'has_cli'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_global' => 'boolean',
        'is_git' => 'boolean',
        'is_git_supported' => 'boolean',
        'is_cloud_curl_script' => 'boolean',
        'has_cli' => 'boolean',
        'required_php_extensions' => 'array',
        'required_dependencies' => 'array',
        'supported_databases' => 'array',
        'configuration_options' => 'array',
        'allowed_web_server_types' => 'array'
    ];

    const TYPE_WORDPRESS = 'wordpress';
    const TYPE_NEXTCLOUD = 'nextcloud';
    const TYPE_DRUPAL = 'drupal';
    const TYPE_CRAFTCMS = 'craftcms';
    const TYPE_MAGENTO = 'magento';
    const TYPE_BAGISTO = 'bagisto';
    const TYPE_CUSTOM = 'custom';

    const WEB_SERVER_APACHE = 'apache';
    const WEB_SERVER_NGINX = 'nginx';
    const WEB_SERVER_LITESPEED = 'litespeed';

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function templates()
    {
        return $this->hasMany(Template::class);
    }

    public function template()
    {
        return $this->belongsTo(Template::class);
    }

    public function supportedControlPanels()
    {
        return $this->belongsToMany(ServerControlPanel::class, 'control_panel_application_types')
            ->withPivot('settings')
            ->withTimestamps();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function gitProvider()
    {
        return $this->belongsTo(GitProvider::class);
    }
}