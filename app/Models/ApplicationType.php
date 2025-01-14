<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicationType extends Model
{
    use HasFactory, SoftDeletes;


// Add new constants for application types
    const TYPE_JOOMLA = 'joomla';
    const TYPE_PHPMYADMIN = 'phpmyadmin';
    const TYPE_LARAVEL = 'laravel';
    const TYPE_STATAMIC = 'statamic';
    const TYPE_MOODLE = 'moodle';
    const TYPE_MAUTIC = 'mautic';
    const TYPE_CUSTOMGITPRIVATE = 'customgitprivate';
    const TYPE_CUSTOMGITPUBLIC = 'customgitpublic';

    // Add new constants for class types
    const CLASS_FRAMEWORK = 'framework';
    const CLASS_CUSTOM = 'custom';

    // Add new constants for CLI frameworks
    const CLI_FRAMEWORK_WORDPRESS = 'wordpress';
    const CLI_FRAMEWORK_DRUPAL = 'drupal';
    const CLI_FRAMEWORK_LARAVEL = 'laravel';
    const CLI_FRAMEWORK_NEXTCLOUD = 'occ-nextcloud';
    const CLI_FRAMEWORK_PLAIN = 'plain-bash';


    protected $fillable = [
      'name',
        'type',
        'server_control_panel_type',
        'class_type',
        'cli_framework',
        'description',
        'is_active',
        'is_global',
        'is_git',
        'repo_type',
        'repo_url',
        'repo_project',
        'repository_name', // Kept as is
        'repo_username',
        'repo_branch',
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
        'allowed_web_server_types',
        'configuration_options',
        'user_id',
        'icon_path',
        'documentation_url',
        'has_cli',
        'cloudpanel_curl', // Kept as is
        'is_cloud_curl_script' // Kept as is
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