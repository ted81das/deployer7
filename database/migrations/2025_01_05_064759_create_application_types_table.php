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
        Schema::create('application_types', function (Blueprint $table) {
            $table->id();
//            $table->timestamps();
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
Schema::dropIfExists('control_panel_application_types');       
 Schema::dropIfExists('application_types');
    }
};
