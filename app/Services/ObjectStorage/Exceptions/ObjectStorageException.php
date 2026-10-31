<?php

namespace App\Services\ObjectStorage\Exceptions;

use RuntimeException;
use Throwable;

class ObjectStorageException extends RuntimeException
{
    public function __construct(
        string $message = '',
        public readonly ?string $errorCode = null,
        int $statusCode = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }
}
