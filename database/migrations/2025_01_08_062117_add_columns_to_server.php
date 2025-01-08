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
        Schema::table('servers', function (Blueprint $table) {
            //
 $table->enum('database_type', ['mariadb', 'mysql'])
                  ->default('mysql')
                  ->nullable();
            $table->text('region')->nullable();
            $table->text('plan')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            //
$table->dropColumn('database_type');
            $table->dropColumn('region');
            $table->dropColumn('plan');
        });
    }
};
