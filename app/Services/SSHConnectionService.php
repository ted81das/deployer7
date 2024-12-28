<?php

namespace App\Services;
use phpseclib3\Crypt\RSA;
use phpseclib3\Net\SSH2;
use phpseclib3\Crypt\PublicKeyLoader;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

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
/*

public function verifyApplicationAccess(string $hostname, string $username, string $keyPath): bool
    {
        try {
            $ssh = new SSH2($hostname);
            
            if (!Storage::disk('local')->exists($keyPath)) {
                \Log::error("SSH key not found at path: {$keyPath}");
                return false;
            }

            $privateKeyContent = Storage::disk('local')->get($keyPath);
            $key = PublicKeyLoader::load($privateKeyContent);

            if (!$ssh->login($username, $key)) {
                \Log::error("SSH login failed for user: {$username}");
                return false;
            }

            // Check if we can access the application directory
            $appPath = "/home/{$username}/";
            $result = $ssh->exec("test -d {$appPath} && echo 'exists'");
            
            return trim($result) === 'exists';

        } catch (\Exception $e) {
            \Log::error("SSH connection failed: " . $e->getMessage());
            return false;
        }
    }

*/
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
