<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('application_types', function (Blueprint $table) {
            // Add new required fields
            $table->enum('server_control_panel_type', [
                'serveravatar',
                'ploi',
                'forge',
                'runcloud',
                'cloudways',
                'spinupwp',
                'cloudstick'
            ])->after('type');
            
            $table->enum('class_type', [
                'framework',
                'custom'
            ])->after('server_control_panel_type');
            
            $table->enum('cli_framework', [
                'wordpress',
                'drupal',
                'laravel',
                'occ-nextcloud',
                'plain-bash'
            ])->after('class_type');
            
            $table->enum('repo_type', [
                'private',
                'public'
            ])->default('public')->after('cli_framework');

            // Modify existing type enum to include new values
            DB::statement("ALTER TABLE application_types MODIFY COLUMN type ENUM(
                'wordpress',
                'nextcloud',
                'drupal',
                'craftcms',
                'magento',
                'bagisto',
                'custom',
                'joomla',
                'phpmyadmin',
                'laravel',
                'statamic',
                'moodle',
                'mautic',
                'customgitprivate',
                'customgitpublic'
            ) NOT NULL");

            // Rename existing columns (except repository_name which stays as is)
            $table->renameColumn('repository', 'repo_url');
            $table->renameColumn('username', 'repo_username');
            $table->renameColumn('branch', 'repo_branch');

            // Remove redundant columns (keeping cloudpanel_curl and is_cloud_curl_script)
            $table->dropColumn([
                'git_deployment_url',
                'is_git_supported'
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('application_types', function (Blueprint $table) {
            // Revert column renames
            $table->renameColumn('repo_url', 'repository');
            $table->renameColumn('repo_username', 'username');
            $table->renameColumn('repo_branch', 'branch');

            // Remove new columns
            $table->dropColumn([
                'server_control_panel_type',
                'class_type',
                'cli_framework',
                'repo_type'
            ]);

            // Restore original columns
            $table->string('git_deployment_url')->nullable();
            $table->boolean('is_git_supported')->default(true);

            // Restore original type enum
            DB::statement("ALTER TABLE application_types MODIFY COLUMN type ENUM(
                'wordpress',
                'nextcloud',
                'drupal',
                'craftcms',
                'magento',
                'bagisto',
                'custom'
            ) NOT NULL");
        });
    }
};
