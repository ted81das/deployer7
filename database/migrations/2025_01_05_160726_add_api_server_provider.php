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
     // Adding new columns as per latest requirements
            $table->string('api_client')->nullable()->after('name'); // API client identifier
            $table->string('api_secret')->nullable()->after('api_client'); // API client secret
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('server_providers', function (Blueprint $table) {
            //
$table->dropColumn(['api_client', 'api_secret']);

        });
    }
};
