<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Application extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        // Core Information
        'name',
        'uuid',
        'hostname',
        'server_id',
        'application_type_id',
        'user_id',
        'domain',
        'web_root',

        // Git Repository Management
        'git_type',
        'git_provider_id',
        'workspace_slug',
        'repository_slug',
        'username',
        'repository_name',
        'project_id',
        'repository',
        'clone_url',
        'file_name',
        'git_repository',
        'git_branch',
        'git_provider',
        'git_token',

        // SSH Keys
        'application_sshkey_private',
        'application_sshkey_pub',
        'deploy_key',

        // Database Configuration
        'database_type',
        'database_name',
        'database_user',
        'database_password',
        'database_host',
        'database_port',

        // Template Configuration
       // 'template_id',
        'is_template',

        // Deployment Information
        'deployment_status',
        'installation_path',
        'application_home_directory',
        'environment_file',
        'ssl_status',
        'last_deployed_at',
        'controlpanel_app_id',
        'deployment_script',
        'post_deployment_script',
        'provisioning_retries',

        // Authentication
        'admin_user',
        'admin_password',
        'admin_email',

        // PHP-FPM Settings
        'pm_type',
        'pm_max_children',
        'pm_start_servers',
        'pm_min_spare_servers',
        'pm_max_spare_servers',
        'pm_process_idle_timeout',
        'pm_max_requests',
        'pm_max_spawn_rate',

        // PHP Configuration
        'max_execution_time',
        'max_input_time',
        'max_input_vars',
        'memory_limit',
        'post_max_size',
        'upload_max_filesize',
        'disabled_functions',
        'open_basedir',
        'auto_prepend_file',
        'php_timezone',

        // SSL Settings
        'ssl',
        'wildcard',
        'key',
        'size',

        // Domain Management
        'domain_status',
        'domain_verification_method',
        'domain_verified_at',
        'domain_aliases',

        // Application Status
        'app_status',
        'app_url',
        'health_check_status',
        'last_health_check_at',

        // Resource Management
        'resource_usage',
        'disk_usage',
        'last_resource_check_at',

        // Backup Management
        'last_backup_at',
        'backup_status',
        'backup_retention_days',

        // Maintenance
        'maintenance_mode',
        'scheduled_maintenance_at',
        'last_maintenance_at',

        // Monitoring and Logs
        'log_retention_days',
        'monitoring_enabled',
        'alert_settings',
        'last_error_log',

        // Meta
        'settings',
        'meta_data'
    ];

    protected $casts = [
        // Dates
        'last_deployed_at' => 'datetime',
        'domain_verified_at' => 'datetime',
        'last_health_check_at' => 'datetime',
        'last_resource_check_at' => 'datetime',
        'last_backup_at' => 'datetime',
        'scheduled_maintenance_at' => 'datetime',
        'last_maintenance_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',

        // Arrays/JSON
        'settings' => 'array',
        'meta_data' => 'array',
        'ssl' => 'array',
        'resource_usage' => 'array',
        'domain_aliases' => 'array',
        'alert_settings' => 'array',

        // Booleans
//        'is_template' => 'boolean',
        'wildcard' => 'boolean',
        'maintenance_mode' => 'boolean',
        'monitoring_enabled' => 'boolean',

        // Integers
        'pm_max_children' => 'integer',
        'pm_start_servers' => 'integer',
        'pm_min_spare_servers' => 'integer',
        'pm_max_spare_servers' => 'integer',
        'pm_process_idle_timeout' => 'integer',
        'pm_max_requests' => 'integer',
        'pm_max_spawn_rate' => 'integer',
        'max_execution_time' => 'integer',
        'max_input_time' => 'integer',
        'max_input_vars' => 'integer',
        'size' => 'integer',
        'provisioning_retries' => 'integer',
        'disk_usage' => 'integer',
        'log_retention_days' => 'integer',
        'backup_retention_days' => 'integer',
        'database_port' => 'integer',

        // Encrypted
        'application_sshkey_private' => 'encrypted',
        'database_password' => 'encrypted',
        'admin_password' => 'encrypted',
        'git_token' => 'encrypted',
        'key' => 'encrypted',

        // Enums
        'git_type' => 'string',
        'pm_type' => 'string',
        'deployment_status' => 'string',
        'app_status' => 'string',
        'health_check_status' => 'string'
    ];

    protected $hidden = [
        'application_sshkey_private',
        'database_password',
        'admin_password',
        'git_token',
        'key'
    ];

    protected $attributes = [
        // PHP-FPM Defaults
        'pm_type' => 'ondemand',
        'pm_max_children' => 20,
        'pm_start_servers' => 2,
        'pm_min_spare_servers' => 1,
        'pm_max_spare_servers' => 3,
        'pm_process_idle_timeout' => 30,
        'pm_max_requests' => 500,
        'pm_max_spawn_rate' => 1,

        // PHP Defaults
        'max_execution_time' => 60,
        'max_input_time' => 60,
        'max_input_vars' => 1600,
        'memory_limit' => '256M',
        'post_max_size' => '128M',
        'upload_max_filesize' => '128M',

        // Application Defaults
        'wildcard' => false,
        'size' => 0,
        'is_template' => false,
        'provisioning_retries' => 0,
        'monitoring_enabled' => true,
        'log_retention_days' => 7,
        'backup_retention_days' => 7,
        'maintenance_mode' => false,
        'deployment_status' => 'pending',
        'app_status' => 'pending'
    ];

    // Constants
    const STATUS_PENDING = 'pending';
    const STATUS_INSTALLING = 'installing';
    const STATUS_ACTIVE = 'active';
    const STATUS_FAILED = 'failed';

    const PM_TYPE_ONDEMAND = 'ondemand';
    const PM_TYPE_DYNAMIC = 'dynamic';
    const PM_TYPE_STATIC = 'static';

    const GIT_TYPE_PRIVATE = 'private';
    const GIT_TYPE_PUBLIC = 'public';

    const HEALTH_STATUS_HEALTHY = 'healthy';
    const HEALTH_STATUS_WARNING = 'warning';
    const HEALTH_STATUS_CRITICAL = 'critical';

    const BACKUP_STATUS_PENDING = 'pending';
    const BACKUP_STATUS_IN_PROGRESS = 'in_progress';
    const BACKUP_STATUS_COMPLETED = 'completed';
    const BACKUP_STATUS_FAILED = 'failed';

    // Validation Rules
    public const VALIDATION_RULES = [
        'name' => ['required', 'string', 'max:255'],
        'hostname' => ['required', 'string', 'regex:/^(?!:\/\/)(?=.{1,255}$)((.{1,63}\.){1,127}(?![0-9]*$)[a-z0-9-]+\.?)$/i'],
        'server_id' => ['required', 'exists:servers,id'],
        'application_type_id' => ['required', 'exists:application_types,id'],
        'git_type' => ['required', 'in:private,public'],
        'git_provider_id' => ['required_if:git_type,private', 'string'],
        'workspace_slug' => ['required_if:git_provider,bitbucket', 'string'],
        'repository_slug' => ['required_if:git_provider,bitbucket', 'string'],
        'username' => ['required_if:git_provider,github', 'string'],
        'repository_name' => ['required_if:git_provider,github', 'string'],
        'project_id' => ['required_if:git_provider,gitlab', 'string'],
        'repository' => ['required_if:git_type,private', 'string'],
        'clone_url' => ['required_if:git_type,public', 'url'],
        'file_name' => ['nullable', 'string']
    ];

    // Relationships
    public function server()
    {
        return $this->belongsTo(Server::class);
    }

    public function applicationType()
    {
        return $this->belongsTo(ApplicationType::class);
    }

    public function template()
    {
        return $this->belongsTo(Template::class, 'template_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('app_status', self::STATUS_ACTIVE);
    }

    public function scopeTemplate($query)
    {
        return $query->where('is_template', true);
    }

    public function scopeHealthy($query)
    {
        return $query->where('health_check_status', self::HEALTH_STATUS_HEALTHY);
    }

    // Custom Methods
    public function isPrivateRepository(): bool
    {
        return $this->git_type === self::GIT_TYPE_PRIVATE;
    }

    public function isTemplate(): bool
    {
        return $this->is_template;
    }

    public function isActive(): bool
    {
        return $this->app_status === self::STATUS_ACTIVE;
    }

    public function isHealthy(): bool
    {
        return $this->health_check_status === self::HEALTH_STATUS_HEALTHY;
    }
}
