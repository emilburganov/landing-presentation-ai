<?php

namespace App\Services\AI\Exceptions;

class AIUnauthorizedException extends AIException
{
    public function __construct(?array $raw = null)
    {
        parent::__construct(
            message: 'AI authorization failed: incorrect API key.',
            statusCode: 401,
            raw: $raw
        );
    }
}
