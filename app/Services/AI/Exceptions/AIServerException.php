<?php

namespace App\Services\AI\Exceptions;

class AIServerException extends AIException
{
    public function __construct(?array $raw = null)
    {
        parent::__construct(
            message: 'AI provider server error.',
            statusCode: 503,
            raw: $raw
        );
    }
}
