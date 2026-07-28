<?php

namespace App\Services\Contact\Exceptions;

use RuntimeException;

class ContactNotificationFailedException extends RuntimeException
{
    public function __construct(
        string                 $message = 'Failed to send contact notification emails.',
        public readonly ?array $raw = null,
    )
    {
        parent::__construct($message);
    }
}
