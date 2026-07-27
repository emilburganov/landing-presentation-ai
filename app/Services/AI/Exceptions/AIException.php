<?php

namespace App\Services\AI\Exceptions;

use Exception;

class AIException extends Exception
{
    public function __construct(
        string                 $message,
        public readonly ?int   $statusCode = 500,
        public readonly ?array $raw = null,
    )
    {
        parent::__construct($message);
    }
}
