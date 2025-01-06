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
        'is_git_supported',
        'git_deployment_url',
        'default_branch',
        'deployment_script_template',
        'post_deployment_script',
        'environment_template',
        'required_php_extensions',
        'required_dependencies',
        'minimum_php_version',
        'recommended_php_version',
        'supported_databases',
        'default_web_server',
        'configuration_options',
        'user_id',
        'icon_path',
        'documentation_url'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_global' => 'boolean',
        'is_git_supported' => 'boolean',
        'required_php_extensions' => 'array',
        'required_dependencies' => 'array',
        'supported_databases' => 'array',
        'configuration_options' => 'array'
    ];

    const TYPE_WORDPRESS = 'wordpress';
    const TYPE_NEXTCLOUD = 'nextcloud';
    const TYPE_DRUPAL = 'drupal';
    const TYPE_CRAFTCMS = 'craftcms';
    const TYPE_MAGENTO = 'magento';
    const TYPE_BAGISTO = 'bagisto';
    const TYPE_CUSTOM = 'custom';

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function templates()
    {
        return $this->hasMany(Template::class);
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
}

