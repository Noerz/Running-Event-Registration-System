<?php

namespace App\Services\ObjectStorage\Exceptions;

class AuthenticationException extends ObjectStorageException
{
    public function __construct(string $message = 'Autentikasi object storage gagal.', ?\Throwable $previous = null)
    {
        parent::__construct(
            message: $message,
            errorCode: 'unauthenticated',
            statusCode: 401,
            previous: $previous
        );
    }
}
