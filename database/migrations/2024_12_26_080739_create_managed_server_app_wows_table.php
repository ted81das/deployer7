<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('managed_server_app_wows', function (Blueprint $table) {
            // Primary Keys
            $table->id();
            $table->uuid('uuid')->unique();
            
            // Foreign Keys
//            $table->foreignId('managed_server_id')
  //                ->constrained('managed_servers')
      //            ->onDelete('cascade');

            // Application Identification
            $table->string('application_name');
            $table->string('userslug', 5)->unique();
            $table->string('app_hostname')->unique();
            
            // Admin Credentials (Encrypted)
            $table->string('app_miniadmin_username');
            $table->string('app_miniadmin_email');
            $table->text('app_miniadmin_password');  // Encrypted
            
            // System User Details
            $table->string('application_user')->unique();  // Random generated username
            $table->text('system_password');              // Encrypted
            $table->string('application_user_id')->unique();
            $table->json('system_user_info')->nullable();
            
            // Database Credentials (Encrypted)
            $table->string('db_name')->unique();
            $table->string('db_username')->unique();
            $table->text('db_password');                  // Encrypted
            
            // Application Configuration
            $table->string('php_version');
            $table->string('webroot')->default('');
            $table->integer('git_provider_id');
            $table->string('clone_url');
            $table->string('branch')->default('main');
            
            // Connection Status
            $table->boolean('phpseclib_connection_status')
                  ->default(false)
                  ->index();

            // Timestamps and Soft Delete
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['application_user', 'deleted_at']);
            $table->index(['app_hostname', 'deleted_at']);
            $table->index(['userslug', 'deleted_at']);
        });

        // Create related tables for command logging
        Schema::create('wp_cli_command_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('managed_server_app_wow_id')
                  ->constrained('managed_server_app_wows')
                  ->onDelete('cascade');
            $table->foreignId('executed_by')
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->text('command');
            $table->longText('output')->nullable();
            $table->boolean('success')->default(false);
            $table->timestamp('executed_at');
            $table->timestamps();
            
            $table->index(['managed_server_app_wow_id', 'executed_at']);
        });

        // Create table for SSH key pairs
        Schema::create('ssh_key_pairs', function (Blueprint $table) {
            $table->id();
            $table->string('application_user_id')->unique();
            $table->foreign('application_user_id')
                  ->references('application_user_id')
                  ->on('managed_server_app_wows')
                  ->onDelete('cascade');
            $table->timestamp('key_generated_at');
            $table->timestamp('last_rotation_at')->nullable();
            $table->timestamps();
            
            $table->index('key_generated_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('wp_cli_command_logs');
        Schema::dropIfExists('ssh_key_pairs');
        Schema::dropIfExists('managed_server_app_wows');
    }
};

