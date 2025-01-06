<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Template extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        // Core Information
        'name',
        'uuid',
        'slug',
        'description',
        'is_active',
        'type',                       // wordpress, nextcloud, craftcms, moodle, drupal
        'user_id',
        'version',
        'is_public',
        'preview_url',
        'demo_url',

        // Git Repository Fields
        'git_type',                    // private or public
        'file_name',                   // Unique file identifier
        'username',                    // Repository username
        'repository_name',             // Repository name
        'repository',                  // Full repository path
        'git_provider_id',             // Provider account ID
        'workspace_slug',              // Bitbucket workspace
        'repository_slug',             // Bitbucket repository
        'project_id',                  // GitLab project ID
        'clone_url',                   // Public repository URL
        'git_branch',
        'git_provider',
        'git_token',

        // Server Configuration
        'web_server_type',             // nginx, apache
        'php_version',
        'supported_php_versions',      // array
        'required_php_extensions',     // array
        'application_root_directory',
        'public_directory',
        'storage_directory',
        'logs_directory',

        // Database Configuration
        'database_type',               // mysql, postgresql, etc
        'database_version',
        'database_schema',
        'database_seeder_path',
        'default_database_prefix',
        'migration_scripts',           // array of scripts

        // Environment Configuration
        'environment_template',        // .env template
        'required_services',          // redis, memcached, etc
        'cron_jobs',                  // array of required crons

        // Default Credentials
        'default_admin_user',
        'default_admin_email',
        'default_admin_password',
        'default_database_user',
        'default_database_password',

        // Server Requirements
        'minimum_cpu_cores',
        'minimum_ram_mb',
        'minimum_storage_mb',
        'recommended_cpu_cores',
        'recommended_ram_mb',
        'recommended_storage_mb',

        // Deployment Configuration
        'deployment_type',            // git, composer, archive
        'deployment_script',          // main deployment script
        'post_deployment_script',     // post-deployment tasks
        'backup_script',             // backup procedure
        'health_check_url',          // health check endpoint
        'success_url',               // post-installation URL

        // Security Configuration
        'requires_ssl',
        'ssl_configuration',         // SSL requirements
        'security_headers',         // required headers

        // Meta Information
        'settings',                 // JSON additional settings
        'meta_data',               // JSON metadata
        'documentation_url',
        'support_url',
        'changelog',
        'last_tested_at',
        'last_updated_at'
    ];

    protected $casts = [
        // Arrays/JSON
        'supported_php_versions' => 'array',
        'required_php_extensions' => 'array',
        'migration_scripts' => 'array',
        'required_services' => 'array',
        'cron_jobs' => 'array',
        'security_headers' => 'array',
        'settings' => 'array',
        'meta_data' => 'array',
        'ssl_configuration' => 'array',

        // Booleans
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'requires_ssl' => 'boolean',

        // Integers
        'minimum_cpu_cores' => 'integer',
        'minimum_ram_mb' => 'integer',
        'minimum_storage_mb' => 'integer',
        'recommended_cpu_cores' => 'integer',
        'recommended_ram_mb' => 'integer',
        'recommended_storage_mb' => 'integer',

        // Dates
        'last_tested_at' => 'datetime',
        'last_updated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',

        // Encrypted
        'git_token' => 'encrypted',
        'default_admin_password' => 'encrypted',
        'default_database_password' => 'encrypted'
    ];

    protected $hidden = [
        'git_token',
        'default_admin_password',
        'default_database_password'
    ];

    protected $attributes = [
        'is_active' => true,
        'is_public' => false,
        'git_branch' => 'main',
        'requires_ssl' => true,
        'deployment_type' => 'git',
        'minimum_cpu_cores' => 1,
        'minimum_ram_mb' => 1024,
        'minimum_storage_mb' => 5120
    ];

    // Constants
    const TYPE_WORDPRESS = 'wordpress';
    const TYPE_NEXTCLOUD = 'nextcloud';
    const TYPE_CRAFTCMS = 'craftcms';
    const TYPE_MOODLE = 'moodle';
    const TYPE_DRUPAL = 'drupal';

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    const DEPLOYMENT_TYPE_GIT = 'git';
    const DEPLOYMENT_TYPE_COMPOSER = 'composer';
    const DEPLOYMENT_TYPE_ARCHIVE = 'archive';

    const GIT_TYPE_PRIVATE = 'private';
    const GIT_TYPE_PUBLIC = 'public';

    // Validation Rules
    public static array $rules = [
        'name' => ['required', 'string', 'max:255'],
        'type' => ['required', 'in:wordpress,nextcloud,craftcms,moodle,drupal'],
        'git_type' => ['required', 'in:private,public'],
        'file_name' => ['required', 'string'],
        'username' => ['required_if:git_type,private', 'string'],
        'repository_name' => ['required_if:git_type,private', 'string'],
        'repository' => ['required_if:git_type,private', 'string'],
        'git_provider_id' => ['required_if:git_type,private', 'string'],
        'workspace_slug' => ['required_if:git_provider,bitbucket', 'string'],
        'repository_slug' => ['required_if:git_provider,bitbucket', 'string'],
        'project_id' => ['required_if:git_provider,gitlab', 'string'],
        'clone_url' => ['required_if:git_type,public', 'url'],
        'php_version' => ['required', 'string'],
        'database_type' => ['required', 'string'],
        'minimum_ram_mb' => ['required', 'integer', 'min:512'],
        'minimum_storage_mb' => ['required', 'integer', 'min:1024']
    ];

    // Relationships
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'template_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    // Helper Methods
    public function isPrivateRepository(): bool
    {
        return $this->git_type === self::GIT_TYPE_PRIVATE;
    }

    public function requiresSsl(): bool
    {
        return $this->requires_ssl;
    }

    public function isGitDeployment(): bool
    {
        return $this->deployment_type === self::DEPLOYMENT_TYPE_GIT;
    }

    public function getFullRepositoryPath(): string
    {
        return $this->isPrivateRepository() 
            ? "{$this->username}/{$this->repository_name}" 
            : $this->clone_url;
    }

    public function meetsServerRequirements(array $serverSpecs): bool
    {
        return $serverSpecs['cpu_cores'] >= $this->minimum_cpu_cores &&
               $serverSpecs['ram_mb'] >= $this->minimum_ram_mb &&
               $serverSpecs['storage_mb'] >= $this->minimum_storage_mb;
    }

    public function getDeploymentScript(): string
    {
        return $this->deployment_script ?? $this->generateDefaultDeploymentScript();
    }

    protected function generateDefaultDeploymentScript(): string
    {
        // Implementation for generating default deployment script based on template type
        return match($this->type) {
            self::TYPE_WORDPRESS => $this->getWordPressDeploymentScript(),
            self::TYPE_NEXTCLOUD => $this->getNextcloudDeploymentScript(),
            self::TYPE_CRAFTCMS => $this->getCraftCMSDeploymentScript(),
            self::TYPE_MOODLE => $this->getMoodleDeploymentScript(),
            self::TYPE_DRUPAL => $this->getDrupalDeploymentScript(),
            default => '',
        };
    }

    protected function getWordPressDeploymentScript(): string
    {
        // WordPress specific deployment script
        return '';
    }

    // Add other application-specific deployment script methods
}

