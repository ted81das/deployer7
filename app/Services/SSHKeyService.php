<?php

namespace App\Services;
use phpseclib3\Crypt\RSA;
class SSHKeyService
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


}
