<?php

namespace App\Exceptions;

use Exception;

class ServerProvisioningException extends Exception
{
    //
 /**
     * Create a new server provisioning exception instance.
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     * @return void
     */
    public function __construct(string $message = "", int $code = 0, ?\Throwable $previous = null)
    {
        $message = $message ?: 'Server provisioning failed';
        parent::__construct($message, $code, $previous);
    }

    /**
     * Report or log the exception.
     *
     * @return void
     */
    public function report(): void
    {
        \Log::error('Server Provisioning Error: ' . $this->getMessage(), [
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString()
        ]);
    }

    /**
     * Get the default error message.
     *
     * @return string
     */
    public function getDefaultMessage(): string
    {
        return 'An error occurred during server provisioning.';
    }


}
