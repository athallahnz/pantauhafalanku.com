<?php

namespace App\Exceptions;

use RuntimeException;

class PasskeyException extends RuntimeException
{
    public static function verificationFailed(?\Throwable $previous = null): self
    {
        return new self('Passkey tidak dapat diverifikasi.', 0, $previous);
    }
}
