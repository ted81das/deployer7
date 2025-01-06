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
        Schema::table('server_providers', function (Blueprint $table) {
            //
$table->uuid('uuid')->after('id')->unique(); // Ensure this column is unique
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('server_providers', function (Blueprint $table) {
            //
 $table->dropColumn('uuid'); // Remove the UUID column if rolling back       
 });
    }
};
