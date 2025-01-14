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
        Schema::create('cli_commands', function (Blueprint $table) {
            $table->id();
           $table->uuid('uuid')->unique();
        $table->string('cli_framework'); // wordpress, occ-nextcloud, laravel, drupal, bash
        $table->string('slug')->unique();
        $table->string('label');
        $table->foreignId('command_section_id')->constrained('cli_command_sections');
        $table->text('command');
        $table->json('allowed_server_types');
        $table->boolean('is_dangerous')->default(false);
        $table->boolean('requires_sudo')->default(false);
        $table->json('parameters')->nullable();
             $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cli_commands');
    }
};
