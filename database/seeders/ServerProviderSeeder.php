<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB; // Ensure this line is included


class ServerProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
$serverProviders = [
            [
                'name' => 'Vultr',
//                'uuid' => 'vultr-' . \Str::uuid(),
                'provider_type' => 'vultr',
                'regions' => json_encode([
                    'ewr', // New Jersey
                    'sfo', // San Francisco
                    'lax', // Los Angeles
                    'ami', // Miami
                    'ord', // Chicago
                    'tokyo', // Tokyo
                    'singapore' // Singapore
                ]), // JSON array of regions
                'plans' => json_encode([
                    'vc2-1c-1gb', // 1 CPU, 1GB RAM
                    'vc2-2c-2gb', // 2 CPU, 2GB RAM
                    'vc2-4c-4gb', // 4 CPU, 4GB RAM
                    'vc2-6c-8gb', // 6 CPU, 8GB RAM
                    'vc2-8c-16gb', // 8 CPU, 16GB RAM
                    'vc2-advanced', // Advanced Plan
                    'vc2-premium' // Premium Plan
                ]), // JSON array of available plans
                'api_client' => null, // Set to null
                'api_secret' => null, // Set to null
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Linode',
  //              'uuid' => 'linode-' . \Str::uuid(),
                'provider_type' => 'linode',
                'regions' => json_encode([
                    'us-east', // New Jersey
                    'us-west', // California
                    'eu-central', // Frankfurt
                    'ap-south', // Mumbai
                    'eu-west', // London
                    'ap-northeast', // Tokyo
                    'us-south' // Dallas
                ]), // JSON array of regions
                'plans' => json_encode([
                    'g6-standard-1', // 1 CPU, 2GB RAM
                    'g6-standard-2', // 2 CPU, 4GB RAM
                    'g6-standard-4', // 4 CPU, 8GB RAM
                    'g6-standard-8', // 8 CPU, 16GB RAM
                    'g6-standard-16', // 16 CPU, 32GB RAM
                    'g6-1gb', // 1GB Plan
                    'g6-2gb' // 2GB Plan
                ]), // JSON array of available plans
                'api_client' => null, // Set to null
                'api_secret' => null, // Set to null
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'AWS Lightsail',
   //             'uuid' => 'lightsail-' . \Str::uuid(),
                'provider_type' => 'aws_lightsail',
                'regions' => json_encode([
                    'us-east-1', // N. Virginia
                    'us-west-1', // N. California
                    'us-east-2', // Ohio
                    'eu-west-1', // Ireland
                    'ap-southeast-1', // Singapore
                    'ap-south-1', // Mumbai
                    'ca-central-1' // Toronto
                ]), // JSON array of regions
                'plans' => json_encode([
                    'nano', // 1 vCPU, 512 MB RAM
                    'small', // 1 vCPU, 2 GB RAM
                    'medium', // 2 vCPU, 4 GB RAM
                    'large', // 2 vCPU, 8 GB RAM
                    'xlarge', // 4 vCPU, 16 GB RAM
                    '2gb', // 2GB Plan
                    '8gb' // 8GB Plan
                ]), // JSON array of available plans
                'api_client' => null, // Set to null
                'api_secret' => null, // Set to null
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Google Cloud',
   //             'uuid' => 'google-cloud-' . \Str::uuid(),
                'provider_type' => 'google',
                'regions' => json_encode([
                    'us-central1', // Iowa
                    'us-east1', // South Carolina
                    'us-west1', // Oregon
                    'europe-west1', // Belgium
                    'asia-east1', // Taiwan
                    'northamerica-northeast1', // Montreal
                    'asia-southeast1' // Singapore
                ]), // JSON array of regions
                'plans' => json_encode([
                    'f1-micro', // 1 vCPU, 0.6 GB RAM
                    'g1-small', // 1 vCPU, 1.7 GB RAM
                    'n1-standard-1', // 1 vCPU, 3.75 GB RAM
                    'n1-standard-2', // 2 vCPU, 7.5 GB RAM
                    'n1-standard-4', // 4 vCPU, 15 GB RAM
                    'n1-standard-8', // 2GB Plan
                    'n1-standard-16' // 8GB Plan
                ]), // JSON array of available plans
                'api_client' => null, // Set to null
                'api_secret' => null, // Set to null
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hetzner',
   //             'uuid' => 'hetzner-' . \Str::uuid(),
                'provider_type' => 'hetzner',
                'regions' => json_encode([
                    'nbg1', // Nuremberg
                    'fsn1', // Falkenstein
                    'nbg2', // Nuremberg
                    'fsn2', // Falkenstein
                    'hil',
                    'ash',
                    'sin',
                    'fr1' // Frankfurt
                ]), // JSON array of regions
                'plans' => json_encode([
                    'cx11', // 1 vCPU, 2 GB RAM
                    'cx21', // 2 vCPU, 4 GB RAM
                    'cx31', // 2 vCPU, 8 GB RAM
                    'cx41', // 4 vCPU, 8 GB RAM
                    'cx51' // 4 vCPU, 16 GB RAM
                ]), // JSON array of available plans
                'api_client' => null, // Set to null
                'api_secret' => null, // Set to null
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('server_providers')->insert($serverProviders);


    }
}
