<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Server extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'uuid',
        'server_ip',
        'server_ipv6',
        'hostname',
        'server_control_panel_id',
        'owner_user_id',
        'owner_email',
        'root_password',
        'server_sshkey_public',
        'server_sshkey_private',
        'public_key',
        'operating_system',
        'version',
        'arch',
        'cores',
        'web_server',
        'server_status',
        'controlpanel_server_id',
        'serveravatar_org_id',
        'memory',
        'cpu',
        'php_version',
        'root_password_authentication',
        'permit_root_login',
        'country_code',
        'owned_by',
        'is_shared',
        'expires_in_days',
        'mapped_plan',
        'sizeSlug',
        'mapped_region',
        'control_panel_type',
        'provider_id',
        'provider',
        'ssh_port',
        'provider_server_id',
        'provisioning_status',
        'database_type',
        'region',
        'plan',
        'user_id'
    ];

    protected $casts = [
        'is_shared' => 'boolean',
        'expires_in_days' => 'integer',
        'ssh_port' => 'integer',
        'root_password_authentication' => 'boolean',
        'permit_root_login' => 'boolean',
        'server_sshkey_private' => 'encrypted',
        'root_password' => 'encrypted',
        'meta_data' => 'array',
        'settings' => 'array'
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_PROVISIONING = 'provisioning';
    const STATUS_ACTIVE = 'active';
    const STATUS_FAILED = 'failed';

    const WEBSERVER_NGINX = 'nginx';
    const WEBSERVER_APACHE2 = 'apache2';
    const WEBSERVER_OPENLITESPEED = 'openlitespeed';
const WEBSERVER_NGINX_APACHE = 'nginx-apache';  // New constant
    const WEBSERVER_MERN = 'mern';


    protected $hidden = [
        'root_password',
        'server_sshkey_private',
        'public_key'
    ];

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

/*    public function provider()
    {
        return $this->belongsTo(ServerProvider::class);
    }*/

    public function controlPanel()
    {
        return $this->belongsTo(ServerControlPanel::class, 'server_control_panel_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

// Server Migration
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Basic Server Information
            $table->string('name');
            $table->string('hostname');
            $table->string('server_ip')->nullable();
            $table->string('server_ipv6')->nullable();
            
            // Control Panel & Provider Information
            $table->foreignId('server_control_panel_id')->constrained();
            $table->enum('control_panel_type', [
                'serveravatar', 
                'cloudways', 
                'ploi', 
                'spinupwp',
                'forge'
            ]);
            $table->foreignId('provider_id')->nullable()->constrained('server_providers');
            $table->string('provider_server_id')->nullable();
            $table->string('serveravatar_org_id')->nullable();
            $table->string('controlpanel_server_id')->nullable();
            
            // Owner Information
            $table->foreignId('owner_user_id')->constrained('users');
            $table->string('owner_email');
            $table->foreignId('user_id')->constrained('users');
            
            // Authentication & Security
            $table->text('root_password')->nullable();
            $table->text('server_sshkey_public')->nullable();
            $table->text('server_sshkey_private')->nullable();
            $table->text('public_key')->nullable();
            $table->boolean('root_password_authentication')->default(false);
            $table->boolean('permit_root_login')->default(false);
            $table->integer('ssh_port')->default(22);
            
            // System Specifications
            $table->string('operating_system')->nullable();
            $table->string('version')->nullable();
            $table->string('arch')->nullable();
            $table->integer('cores')->nullable();
            $table->enum('web_server', ['apache', 'nginx', 'litespeed'])->nullable();
            $table->string('memory')->nullable();
            $table->string('cpu')->nullable();
            $table->string('php_version')->default('8.1');
            
            // Server Status & Configuration
            $table->string('server_status')->nullable();
            $table->enum('provisioning_status', [
                'pending',
                'provisioning',
                'active',
                'failed'
            ])->default('pending');
            $table->string('country_code')->nullable();
            $table->string('owned_by')->nullable();
            $table->boolean('is_shared')->default(false);
            $table->integer('expires_in_days')->nullable();
            
            // Plan & Region
            $table->enum('mapped_plan', [
                'starter',
                'advanced',
                'premium'
            ])->nullable();
            $table->string('sizeSlug')->nullable();
            $table->enum('mapped_region', [
                'us-east',
                'us-west',
                'us-central',
                'eu-central',
                'eu-west',
                'apac-east',
                'apac-middle',
                'apac-southeast'
            ])->nullable();

            // Additional Settings
            $table->json('settings')->nullable();
            $table->json('meta_data')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('uuid');
            $table->index('server_ip');
            $table->index('hostname');
            $table->index('provisioning_status');
            $table->index('server_status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('servers');
    }
};

// ApplicationType Model
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
        return $this->hasMany(ApplicationTemplate::class);
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

// ApplicationType Migration
return new class extends Migration
{
    public function up()
    {
        Schema::create('application_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', [
                'wordpress',
                'nextcloud',
                'drupal',
                'craftcms',
                'magento',
                'bagisto',
                'custom'
            ]);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_global')->default(false);
            $table->boolean('is_git_supported')->default(true);
            $table->string('git_deployment_url')->nullable();
            $table->string('default_branch')->default('main');
            $table->text('deployment_script_template');
            $table->text('post_deployment_script')->nullable();
            $table->text('environment_template')->nullable();
            $table->json('required_php_extensions')->nullable();
            $table->json('required_dependencies')->nullable();
            $table->string('minimum_php_version')->default('8.1');
            $table->string('recommended_php_version')->default('8.2');
            $table->json('supported_databases')->nullable();
            $table->enum('default_web_server', ['apache', 'nginx', 'litespeed'])->default('nginx');
            $table->json('configuration_options')->nullable();
            $table->foreignId('user_id')->constrained();
            $table->string('icon_path')->nullable();
            $table->string('documentation_url')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('is_active');
            $table->index('is_global');
        });

        // Pivot table for control panel support
        Schema::create('control_panel_application_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_control_panel_id')->constrained()->onDelete('cascade');
            $table->foreignId('application_type_id')->constrained()->onDelete('cascade');
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(
                ['server_control_panel_id', 'application_type_id'],
                'panel_app_type_unique'
            );
        });
    }

    public function down()
    {
        Schema::dropIfExists('control_panel_application_types');
        Schema::dropIfExists('application_types');
    }
};

