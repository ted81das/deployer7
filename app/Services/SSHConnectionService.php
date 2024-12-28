<?php

namespace App\Services;
use phpseclib3\Crypt\RSA;
use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Models\ManagedServerAppWow;
use Illuminate\Support\Facades\Crypt;

class SSHConnectionService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

public function generateKeyPair(string $username): array
    {
        $privateKey = RSA::createKey();
        $publicKey = $privateKey->getPublicKey();

        return [
            'privateKey' => $privateKey->toString('OpenSSH'),
            'publicKey' => $publicKey->toString('OpenSSH')
        ];
    }



  /**
     * Execute a command on the remote server using application-specific SSH key
     */
    public function executeCommand(ManagedServerAppWow $app, string $command): string
    {
        try {
            // Validate required data
            if (empty($app->application_sshkey_private) || 
                empty($app->application_user) || 
                empty($app->app_hostname)) {
                throw new \Exception('Missing required SSH connection data');
            }

            // Get decrypted private key from database
            $privateKeyString = Crypt::decryptString($app->application_sshkey_private);
            
            // Load the private key
            $privateKey = PublicKeyLoader::load($privateKeyString);

            // Create SSH connection
            $ssh = new SSH2($app->app_hostname);

            // Attempt to login with the application user and private key
            if (!$ssh->login($app->application_user, $privateKey)) {
                throw new \Exception('SSH authentication failed');
            }

            // Build the full command with proper directory change
            $appPath = "/home/{$app->application_user}/{$app->application_name}/public_html";
            $fullCommand = "cd {$appPath} && {$command}";

            // Execute the command
            $output = $ssh->exec($fullCommand);
            
            // Check command exit status
            if ($ssh->getExitStatus() !== 0) {
                throw new \Exception("Command execution failed with output: " . $output);
            }

            // Log successful execution
            Log::info('WP-CLI command executed successfully', [
                'app_id' => $app->id,
                'command' => $command,
                'user' => $app->application_user
            ]);

            return $output;

        } catch (\Exception $e) {
            Log::error('Command execution failed: ' . $e->getMessage(), [
                'app_id' => $app->id,
                'command' => $command,
                'user' => $app->application_user ?? 'unknown'
            ]);
            throw $e;
        } finally {
            if (isset($ssh)) {
                $ssh->disconnect();
            }
        }
    }


 /**
     * Test SSH connection using provided credentials
     * 
     * @param string $hostname Server hostname
     * @param string $username Application username
     * @param string $privateKey Decrypted private key string
     * @return bool Connection status
     */
    public function testConnection(string $hostname, string $username, string $privateKey): bool
    {
        try {
            // Initialize SSH connection
            $ssh = new SSH2($hostname);
            
           // $privateKey = stripcslashes($privateKey);
            $privateKey = str_replace('\n', "\n", $privateKey);

            // Load private key from string
            $key = PublicKeyLoader::load($privateKey);

            // Attempt login
            if (!$ssh->login($username, $key)) {
                Log::error("SSH login failed for user {$username} on {$hostname}");
              //  dd($privateKey,$key,'HERE FAILED');
                return false;
            }

            // Verify application directory access
            $appPath = "/home/{$username}/";
            $result = $ssh->exec("test -d {$appPath} && echo 'exists'");
            //       dd($privateKey,$key,'HERE Success');
            $hasAccess = trim($result) === 'exists';
            
            if (!$hasAccess) {
                Log::error("Directory access failed for {$appPath}");
            }

            return $hasAccess;
            
            //return true;

        } catch (\Exception $e) {
            Log::error("SSH connection test failed: " . $e->getMessage());
            return false;
        }

    }




}
