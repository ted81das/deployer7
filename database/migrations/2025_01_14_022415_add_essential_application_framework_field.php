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
        Schema::table('applications', function (Blueprint $table) {
            //
         // Essential framework fields
                     
            // Essential SSL fields
            $table->boolean('force_ssl')->default(true)->after('ssl_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            //
        $table->dropColumn([
                'framework_type',
                'framework_settings',
                'db_prefix',
                'admin_email',
                'admin_username',
                'site_title',
                'force_ssl'
            ]);
        });
    }
};
