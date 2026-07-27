<?php

namespace App\Services\AI\Exceptions;

class AIConnectionException extends AIException
{
    public function __construct(?array $raw = null)
    {
        parent::__construct(
            message: 'AI connection failed (timeout or network issue).',
            statusCode: 504,
            raw: $raw
        );
    }
}

