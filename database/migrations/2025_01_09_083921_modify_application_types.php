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
        Schema::table('application_types', function (Blueprint $table) {
            //

// Git related fields
//            $table->boolean('is_git')->default(false);
//            $table->string('file_name')->nullable();
//            $table->foreignId('git_provider_id')->nullable()->constrained('git_providers');
            $table->string('repository')->nullable();
            $table->string('username')->nullable();
            $table->string('repository_name')->nullable();
            $table->string('branch')->nullable();
            
            // Make existing fields nullable
            $table->text('environment_template')->nullable()->change();
            $table->json('required_php_extensions')->nullable()->change();
            $table->json('required_dependencies')->nullable()->change();
            $table->string('minimum_php_version')->nullable()->change();
            $table->string('recommended_php_version')->nullable()->change();
            $table->json('supported_databases')->nullable()->change();
            $table->string('default_web_server')->nullable()->change();
            $table->json('configuration_options')->nullable()->change();
            
            // New fields
            $table->json('allowed_web_server_types')->nullable();
  //          $table->foreignId('template_id')->nullable()->constrained('templates');
            $table->text('cloudpanel_curl')->nullable();
            $table->text('deployment_script')->nullable();
            $table->boolean('is_cloud_curl_script')->default(false);
            $table->boolean('has_cli')->default(false);


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_types', function (Blueprint $table) {
            //
 // Remove the new columns
            $table->dropColumn([
                'is_git',
                'file_name',
                'git_provider_id',
                'repository',
                'username',
                'repository_name',
                'branch',
                'allowed_web_server_types',
                'template_id',
                'cloudpanel_curl',
                'deployment_script',
                'is_cloud_curl_script',
                'has_cli'
            ]);
            
            // Revert nullable changes
            $table->text('environment_template')->nullable(false)->change();
            $table->json('required_php_extensions')->nullable(false)->change();
            $table->json('required_dependencies')->nullable(false)->change();
            $table->string('minimum_php_version')->nullable(false)->change();
            $table->string('recommended_php_version')->nullable(false)->change();
            $table->json('supported_databases')->nullable(false)->change();
            $table->string('default_web_server')->nullable(false)->change();
            $table->json('configuration_options')->nullable(false)->change();
        });


    }
};
