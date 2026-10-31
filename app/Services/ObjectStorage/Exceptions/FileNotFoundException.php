<?php

namespace App\Services\ObjectStorage\Exceptions;

class FileNotFoundException extends ObjectStorageException
{
    public function __construct(string $path, ?\Throwable $previous = null)
    {
        parent::__construct(
            message: "File tidak ditemukan di object storage: {$path}",
            errorCode: 'not_found',
            statusCode: 404,
            previous: $previous
        );
    }
}
