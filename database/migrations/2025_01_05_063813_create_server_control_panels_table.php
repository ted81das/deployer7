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
        Schema::create('server_control_panels', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->enum('type', [
                'serveravatar',
                'cloudways',
                'ploi',
                'spinupwp',
                'forge'
            ]);
            $table->enum('authentication_status', [
                'pending_authentication',
                'authenticated',
                'failed'
            ])->default('pending_authentication');
            $table->enum('auth_type', ['bearer', 'oauth2', 'basic'])->default('bearer');
            $table->string('base_url')->nullable();
            $table->string('api_client')->nullable();
            $table->text('api_secret')->nullable();
            $table->text('api_token')->nullable();
            $table->json('available_providers')->nullable();
            $table->json('settings')->nullable();
            $table->json('meta_data')->nullable();
            $table->json('supported_php_versions')->nullable();
            $table->json('supported_web_servers')->nullable();
            $table->json('supported_databases')->nullable();
            $table->json('supported_features')->nullable();
            $table->json('rate_limits')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_authenticated_at')->nullable();
            $table->text('authentication_error')->nullable();
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('authentication_status');
            $table->index('is_active');
          });
// Pivot table for provider support
        Schema::create('provider_control_panel_support', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_provider_id')->constrained()->onDelete('cascade');
            $table->foreignId('server_control_panel_id')->constrained()->onDelete('cascade');
            $table->json('settings')->nullable();
            $table->timestamps();

            $table->unique(
                ['server_provider_id', 'server_control_panel_id'],
                'provider_panel_unique'
            );
        });
 
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
Schema::dropIfExists('provider_control_panel_support'); 
       Schema::dropIfExists('server_control_panels');
    }
};
