<?php

namespace App\Services\Contact\Exceptions;

use RuntimeException;

class CommentAnalysisFailedException extends RuntimeException
{
    public function __construct(
        string                 $message,
        public readonly int    $statusCode = 500,
        public readonly ?array $raw = null,
    )
    {
        parent::__construct($message);
    }
}
