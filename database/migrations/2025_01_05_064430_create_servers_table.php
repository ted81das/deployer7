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
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
//            $table->timestamps();
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
