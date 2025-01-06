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
        Schema::create('server_providers', function (Blueprint $table) {
            $table->id();
//            $table->timestamps();
// $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('provider_type', [
                'digitalocean', 'aws', 'vultr', 'linode', 'gcp', 'hetzner'
            ]);
            $table->json('credentials_schema');
            $table->json('regions');
            $table->json('plans');
            $table->json('features')->nullable();
            $table->json('settings')->nullable();
            $table->string('api_endpoint')->nullable();
            $table->string('api_version')->nullable();
            $table->json('supported_operating_systems');
            $table->integer('min_server_limit')->default(0);
            $table->integer('max_server_limit')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('provider_type');
            $table->index('is_active');   
     });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('server_providers');
    }
};
