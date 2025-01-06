<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->uuid('uuid')->after('id')->unique();
            $table->string('name')->after('uuid');
            $table->foreignId('server_id')->after('name')->constrained();
            $table->foreignId('user_id')->after('server_id')->constrained();
            $table->string('hostname')->after('user_id')
                  ->comment('Fully Qualified Domain Name required');
            $table->string('deployment_status')->default('pending');
            $table->string('app_status')->default('pending');
            
            // Server Configuration
            $table->enum('php_version', ['8.3', '8.2', '8.1', '7.4'])->default('8.2');
            $table->enum('web_server', ['apache', 'nginx', 'litespeed'])->default('nginx');
            $table->string('web_root')->nullable();
            $table->string('application_home_directory')->nullable();
            
            // SSH Keys
            $table->text('application_sshkey_private')->nullable();
            $table->text('application_sshkey_pub')->nullable();
            $table->text('deploy_key')->nullable();
            
            // Database Configuration
            $table->string('database_type')->nullable();
            $table->string('database_name')->nullable();
            $table->string('database_user')->nullable();
            $table->text('database_password')->nullable();
            $table->string('database_host')->nullable();
            $table->integer('database_port')->nullable();
            
            // Git Information
            $table->string('git_repository')->nullable();
            $table->string('git_branch')->default('main');
            $table->string('git_provider')->nullable();
            $table->text('git_token')->nullable();
            
            // Deployment Information
            $table->string('installation_path')->nullable();
            $table->text('environment_file')->nullable();
            $table->string('ssl_status')->nullable();
            $table->timestamp('last_deployed_at')->nullable();
            $table->string('controlpanel_app_id')->nullable();
            $table->text('deployment_script')->nullable();
            $table->text('post_deployment_script')->nullable();
            $table->integer('provisioning_retries')->default(0);
            
            // Authentication
            $table->string('admin_user')->nullable();
            $table->text('admin_password')->nullable();
            $table->string('admin_email')->nullable();
            
            // PHP-FPM Settings
            $table->enum('pm_type', ['ondemand', 'dynamic', 'static'])->default('ondemand');
            $table->integer('pm_max_children')->default(20);
            $table->integer('pm_start_servers')->default(2);
            $table->integer('pm_min_spare_servers')->default(1);
            $table->integer('pm_max_spare_servers')->default(3);
            $table->integer('pm_process_idle_timeout')->default(30);
            $table->integer('pm_max_requests')->default(500);
            $table->integer('pm_max_spawn_rate')->default(1);
            
            // PHP Settings
            $table->integer('max_execution_time')->default(60);
            $table->integer('max_input_time')->default(60);
            $table->integer('max_input_vars')->default(1600);
            $table->string('memory_limit')->default('256M');
            $table->string('post_max_size')->default('128M');
            $table->string('upload_max_filesize')->default('128M');
            $table->text('disabled_functions')->nullable();
            $table->text('open_basedir')->nullable();
            $table->string('auto_prepend_file')->nullable();
            $table->string('php_timezone')->nullable();
            
            // SSL Settings
            $table->json('ssl')->nullable();
            $table->boolean('wildcard')->default(false);
            $table->text('key')->nullable();
            $table->bigInteger('size')->default(0);
            
            // Domain Management
            $table->string('domain')->nullable();
            $table->string('domain_status')->nullable();
            $table->string('domain_verification_method')->nullable();
            $table->timestamp('domain_verified_at')->nullable();
            $table->json('domain_aliases')->nullable();
            
            // Application Status & Monitoring
            $table->string('health_check_status')->nullable();
            $table->timestamp('last_health_check_at')->nullable();
            
            // Resource Management
            $table->json('resource_usage')->nullable();
            $table->bigInteger('disk_usage')->default(0);
            $table->timestamp('last_resource_check_at')->nullable();
            
            // Backup Management
            $table->timestamp('last_backup_at')->nullable();
            $table->string('backup_status')->nullable();
            $table->integer('backup_retention_days')->default(7);
            
            // Maintenance
            $table->boolean('maintenance_mode')->default(false);
            $table->timestamp('scheduled_maintenance_at')->nullable();
            $table->timestamp('last_maintenance_at')->nullable();
            
            // Monitoring and Logs
            $table->integer('log_retention_days')->default(7);
            $table->boolean('monitoring_enabled')->default(true);
            $table->json('alert_settings')->nullable();
            $table->text('last_error_log')->nullable();
            
            // Meta
            $table->json('settings')->nullable();
            $table->json('meta_data')->nullable();
            
            // Soft Deletes
            $table->softDeletes();
            
            // Indexes
            $table->index('uuid');
            $table->index('hostname');
            $table->index('deployment_status');
            $table->index(['server_id', 'user_id']);
            $table->index('app_status');
            $table->index('controlpanel_app_id');
            $table->index('health_check_status');
            $table->index('backup_status');
            $table->index('domain_status');
            $table->index('maintenance_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
             $table->dropIndex(['uuid']);
            //$table->dropIndex(['uuid']);
            $table->dropIndex(['hostname']);
            $table->dropIndex(['deployment_status']);
            $table->dropIndex(['server_id', 'user_id']);
            $table->dropIndex(['app_status']);
            $table->dropIndex(['controlpanel_app_id']);
            $table->dropIndex(['health_check_status']);
            $table->dropIndex(['backup_status']);
            $table->dropIndex(['domain_status']);
            $table->dropIndex(['maintenance_mode']);
            
            // Drop Soft Deletes
            $table->dropSoftDeletes();
            
            // Drop All Added Columns
            $table->dropColumn([
                'uuid',
                'name',
                'server_id',
                'user_id',
                'hostname',
                'deployment_status',
                'app_status',
                'php_version',
                'web_server',
                'web_root',
                'application_home_directory',
                'application_sshkey_private',
                'application_sshkey_pub',
                'deploy_key',
                'database_type',
                'database_name',
                'database_user',
                'database_password',
                'database_host',
                'database_port',
                'git_repository',
                'git_branch',
                'git_provider',
                'git_token',
                'installation_path',
                'environment_file',
                'ssl_status',
                'last_deployed_at',
                'controlpanel_app_id',
                'deployment_script',
                'post_deployment_script',
                'provisioning_retries',
                'admin_user',
                'admin_password',
                'admin_email',
                'pm_type',
                'pm_max_children',
                'pm_start_servers',
                'pm_min_spare_servers',
                'pm_max_spare_servers',
                'pm_process_idle_timeout',
                'pm_max_requests',
                'pm_max_spawn_rate',
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
                'ssl',
                'wildcard',
                'key',
                'size',
                'domain',
                'domain_status',
                'domain_verification_method',
                'domain_verified_at',
                'domain_aliases',
                'health_check_status',
                'last_health_check_at',
                'resource_usage',
                'disk_usage',
                'last_resource_check_at',
                'last_backup_at',
                'backup_status',
                'backup_retention_days',
                'maintenance_mode',
                'scheduled_maintenance_at',
                'last_maintenance_at',
                'log_retention_days',
                'monitoring_enabled',
                'alert_settings',
                'last_error_log',
                'settings',
                'meta_data'
              ]);       
        });
    }
       
};
