<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;  // Add this import
use App\Services\SSHConnectionService;
use Illuminate\Support\Facades\Crypt;  // Add this import
use Illuminate\Support\Facades\Log;

class ManagedServerAppWOW extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $table = 'managed_server_app_wows';

    protected $fillable = [
        'managed_server_id',
        'application_name',
        'app_hostname',
        'app_miniadmin_username',
        'app_miniadmin_email',
        'app_miniadmin_password',
        'application_user',
        'system_password',
        'db_name',
        'db_username',
        'db_password',
        'application_user_id',
        'system_user_info',
        'php_version',
        'webroot',
        'managed_server_id',
        'serveravatar_application_id',
        'git_provider_id',
        'clone_url',
        'branch',
        'phpseclib_connection_status',
        'userslug',
        'application_sshkey_private',
        'application_sshkey_pub',
    ];

 protected $guarded = ['id'];

 // Specify which field should be treated as UUID
    public function newUniqueId()
    {
        return (string) Str::uuid();
    }

    public function uniqueIds()
    {
        return ['uuid'];
    } // Specify which field should be treated as UUID



    protected $casts = [
        'app_miniadmin_password' => 'encrypted',
        'system_password' => 'encrypted',
        'db_password' => 'encrypted',
        'system_user_info' => 'array',
        'phpseclib_connection_status' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'app_miniadmin_password',
        'system_password',
        'db_password',
    ];

    // Relationships
  
    public function sshKeys()
    {
        return $this->hasOne(SshKeyPair::class, 'application_user_id', 'application_user_id');
    }

    public function commandLogs()
    {
        return $this->hasMany(WpCliCommandLog::class);
    }

    // Accessors & Mutators
    public function getPrivateKeyPathAttribute()
    {
        return Storage::path("keys/{$this->application_user}/id_rsa_{$this->application_user}");
    }

    public function getPublicKeyPathAttribute()
    {
        return Storage::path("keys/{$this->application_user}/id_rsa_{$this->application_user}.pub");
    }

    public function getApplicationDirectoryAttribute()
    {
        return "/home/{$this->application_user}/{$this->userslug}_applicationname/public_html";
    }

    // Methods for SSH Key Management
    public function getPrivateKeyContent()
    {
        if (!Storage::exists("keys/{$this->application_user}/id_rsa_{$this->application_user}")) {
            throw new \Exception('Private key not found');
        }
        return Storage::get("keys/{$this->application_user}/id_rsa_{$this->application_user}");
    }

    public function getPublicKeyContent()
    {
        if (!Storage::exists("keys/{$this->application_user}/id_rsa_{$this->application_user}.pub")) {
            throw new \Exception('Public key not found');
        }
        return Storage::get("keys/{$this->application_user}/id_rsa_{$this->application_user}.pub");
    }

    // Methods for Connection Status
   /* public function updateConnectionStatus(bool $status)
    {
        $this->update(['phpseclib_connection_status' => $status]);
    }
    */

    public function isConnected(): bool
    {
        return $this->phpseclib_connection_status;
    }

    // Methods for Application Management
    public function getConnectionDetails(): array
    {
        return [
            'hostname' => $this->app_hostname,
            'username' => $this->application_user,
            'private_key_path' => $this->private_key_path,
            'application_directory' => $this->application_directory,
        ];
    }

    // Scopes
    public function scopeConnected($query)
    {
        return $query->where('phpseclib_connection_status', true);
    }

    public function scopeDisconnected($query)
    {
        return $query->where('phpseclib_connection_status', false);
    }

    // Event Handlers
    protected static function booted()
    {
        static::deleting(function ($app) {
            // Clean up SSH keys when application is deleted
            if ($app->isForceDeleting()) {
                Storage::deleteDirectory("keys/{$app->application_user}");
            }
        });

        static::creating(function ($app) {
            // Generate unique userslug if not provided
            if (empty($app->userslug)) {
                $app->userslug = str_pad(uniqid(), 5, '0', STR_PAD_LEFT);
            }
        });
    }

    // Validation Methods
    public function canExecuteCommands(): bool
    {
        return $this->isConnected() && 
               Storage::exists($this->private_key_path) && 
               $this->application_user;
    }

    public function requiresKeyRotation(): bool
    {
        $keyCreationDate = Storage::lastModified($this->private_key_path);
        $daysOld = now()->diffInDays(\Carbon\Carbon::createFromTimestamp($keyCreationDate));
        
        return $daysOld > config('app.ssh_key_rotation_days', 90);
    }




    // Audit Methods
    public function logCommandExecution(string $command, string $output, bool $success)
    {
        return $this->commandLogs()->create([
            'command' => $command,
            'output' => $output,
            'success' => $success,
            'executed_by' => auth()->id(),
            'executed_at' => now(),
        ]);
    }
    
    
    
    public function checkConnectionStatus(): bool
    {
        $sshService = app(SSHConnectionService::class);
        
      
      
      //  $keyPath = "keys/{$this->userslug}/id_rsa_{$this->application_user}";
        //application_user
        $keyPath = "keys/{$this->application_user}/id_rsa_{$this->application_user}";
        
        $isConnected = $sshService->verifyApplicationAccess(
            config('services.serveravatar.server_hostname'),
            $this->application_user,
            $keyPath
        );

dd($isConnected,$keyPath,$this->application_user,config('services.serveravatar.server_hostname'));

        $this->update(['phpseclib_connection_status' => $isConnected]);
        
        return $isConnected;
    }

    
    
     public function updateConnectionStatus(): bool
    {
        try {
            // Check if we have necessary data
            if (empty($this->application_sshkey_private) || 
                empty($this->application_user) || 
                empty($this->app_hostname)) {
                \Log::error('Missing required SSH connection data for application: ' . $this->id);
                return false;
            }

            // Get decrypted private key from database
            $privateKey = Crypt::decryptString($this->application_sshkey_private);
            
         //   dd($privateKey);

            // Test connection using SSHConnectionService
            $sshService = app(SSHConnectionService::class);
            $isConnected = $sshService->testConnection(
                $this->app_hostname,
                $this->application_user,
                $privateKey
            );

            // Update connection status in database
            $this->update(['phpseclib_connection_status' => $isConnected]);

            if (!$isConnected) {
                \Log::warning('SSH connection failed for application: ' . $this->id);
            }

            return $isConnected;

        } catch (\Exception $e) {
            \Log::error('SSH connection error for application ' . $this->id . ': ' . $e->getMessage());
            $this->update(['phpseclib_connection_status' => false]);
            return false;
        }
    }

    /**
     * Helper method to get decrypted private key
     */
    public function getDecryptedPrivateKeyAttribute(): ?string
    {
        try {
            return $this->application_sshkey_private 
                ? Crypt::decryptString($this->application_sshkey_private)
                : null;
        } catch (\Exception $e) {
            \Log::error('Failed to decrypt private key: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Helper method to get public key
     */
    public function getPublicKeyAttribute(): ?string
    {
        return $this->application_sshkey_pub;
    }
    
    
    
    
}
