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
        Schema::create('cli_frameworks', function (Blueprint $table) {
        $table->id();
        $table->uuid('uuid')->unique();
        $table->string('name'); // wordpress, occ-nextcloud, laravel, drupal, bash
        $table->string('slug')->unique();
        $table->string('startcharacters')->unique()->nullable();
        $table->text('description')->nullable();
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cli_frameworks');
    }
};
