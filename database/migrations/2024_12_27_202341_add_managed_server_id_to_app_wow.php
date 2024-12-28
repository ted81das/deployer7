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
        Schema::table('managed_server_app_wows', function (Blueprint $table) {
            //
 $table->unsignedInteger('managed_server_id')
                ->after('deleted_at')
                ->nullable();

            // Add serveravatar_application_id field
            $table->unsignedInteger('serveravatar_application_id')
                ->after('managed_server_id')
                ->nullable();

            // Add SSH key fields
            $table->text('application_sshkey_private')
                ->after('serveravatar_application_id')
                ->nullable()
                ->comment('SSH private key for the application');

            $table->text('application_sshkey_pub')
                ->after('application_sshkey_private')
                ->nullable()
                ->comment('SSH public key for the application');
     

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('managed_server_app_wows', function (Blueprint $table) {
            //
 $table->dropColumn([
                'managed_server_id',
                'serveravatar_application_id',
                'application_sshkey_private',
                'application_sshkey_pub'
            ]);     

   });
    }
};
