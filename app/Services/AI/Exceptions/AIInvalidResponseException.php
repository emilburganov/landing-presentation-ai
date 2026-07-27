<?php

namespace App\Services\AI\Exceptions;

class AIInvalidResponseException extends AIException
{
    public function __construct(?array $raw = null)
    {
        parent::__construct(
            message: 'AI returned invalid or non-JSON response.',
            statusCode: 502,
            raw: $raw
        );
    }
}
