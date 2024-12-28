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
// First, drop both primary keys and auto_increment
// 1. First drop the foreign key constraint
 // 1. First drop the foreign key constraint

// First check if uuid column exists
        if (!Schema::hasColumn('managed_server_app_wows', 'uuid')) {
            Schema::table('managed_server_app_wows', function (Blueprint $table) {
                // Add uuid column if it doesn't exist
                $table->uuid()->unique()->after('id');
            });

            // Generate UUIDs for existing records
            DB::table('managed_server_app_wows')->whereNull('uuid')->each(function ($record) {
                DB::table('managed_server_app_wows')
                    ->where('id', $record->id)
                    ->update(['uuid' => Str::uuid()]);
            });
        }
    }

    public function down(): void
    {
        Schema::table('managed_server_app_wows', function (Blueprint $table) {
            // Drop the uuid column if needed to rollback
            $table->dropColumn('uuid');
        });
    }
};
