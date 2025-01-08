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
//        Schema::table('servers', function (Blueprint $table) {
            //

   // First, backup existing data
        $existingData = DB::table('servers')
            ->whereNotNull('web_server')
            ->pluck('web_server', 'id');

        Schema::table('servers', function (Blueprint $table) {
            // Drop the existing column
            $table->dropColumn('web_server');
        });

        Schema::table('servers', function (Blueprint $table) {
            // Add the column back with new enum values
            $table->enum('web_server', [
                'apache2',
                'nginx',
                'nginx-apache',
                'mern',
                'openlitespeed'
            ])->nullable();
        });

        // Restore the data with new values
        foreach ($existingData as $id => $value) {
            $newValue = $value === 'apache' ? 'apache2' : 
                       ($value === 'litespeed' ? 'openlitespeed' : $value);
            
            DB::table('servers')
                ->where('id', $id)
                ->update(['web_server' => $newValue]);
        }


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
//        Schema::table('servers', function (Blueprint $table) {
            //
    // Backup existing data
        $existingData = DB::table('servers')
            ->whereNotNull('web_server')
            ->pluck('web_server', 'id');

        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('web_server');
        });

        Schema::table('servers', function (Blueprint $table) {
            // Restore original enum
            $table->enum('web_server', ['apache', 'nginx', 'litespeed'])->nullable();
        });

        // Restore the data with original values
        foreach ($existingData as $id => $value) {
            $oldValue = $value === 'apache2' ? 'apache' : 
                       ($value === 'openlitespeed' ? 'litespeed' : null);
            
            if ($oldValue) {
                DB::table('servers')
                    ->where('id', $id)
                    ->update(['web_server' => $oldValue]);
            }
        }
    }
};
