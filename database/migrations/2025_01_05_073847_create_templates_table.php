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
        Schema::create('templates', function (Blueprint $table) {
//            $table->id();
//            $table->timestamps();
           // Required Core Fields
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->enum('type', ['wordpress', 'nextcloud', 'craftcms', 'moodle', 'drupal']);
            $table->foreignId('user_id')->constrained();
            
            // Required Git Fields
            $table->enum('git_type', ['private', 'public']);
            $table->string('file_name');              // Required for repository identification

            // Required for Private Repositories (nullable but validated in code when git_type is private)
            $table->string('username')->nullable();
            $table->string('repository_name')->nullable();
            $table->string('repository')->nullable();
            $table->string('git_provider_id')->nullable();
            
            // Required for Public Repositories (nullable but validated in code when git_type is public)
            $table->string('clone_url')->nullable();

            // Provider-Specific Fields (nullable but validated based on provider)
            $table->string('workspace_slug')->nullable();    // For Bitbucket
            $table->string('repository_slug')->nullable();   // For Bitbucket
            $table->string('project_id')->nullable();        // For GitLab

            // Optional but Common Fields
            $table->text('description')->nullable();
            $table->string('git_branch')->default('main');
            $table->string('git_provider')->nullable();
            $table->text('git_token')->nullable();
            
            // Default Application Settings (nullable but often needed)
            $table->string('default_admin_user')->nullable();
            $table->string('default_admin_email')->nullable();
            $table->text('default_admin_password')->nullable();
            
            // Deployment Settings (minimal required)
            $table->text('deployment_script')->nullable();
            $table->text('post_deployment_script')->nullable();
            
            // Status and Configuration
            $table->boolean('is_active')->default(true);
            $table->json('settings')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Essential Indexes
            $table->index('type');
            $table->index('git_type');
            $table->index(['type', 'is_active']);
            $table->index('file_name');  

      });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
