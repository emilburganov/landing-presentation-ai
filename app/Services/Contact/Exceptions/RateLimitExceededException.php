<?php

namespace App\Services\Contact\Exceptions;

use RuntimeException;

class RateLimitExceededException extends RuntimeException
{
    public function __construct(
        string $message = 'Too many requests. Try again later.',
        public readonly ?int $retryAfter = null
    ) {
        parent::__construct($message);
    }
}
