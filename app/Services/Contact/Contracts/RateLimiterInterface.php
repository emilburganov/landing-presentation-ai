<?php

namespace App\Services\Contact\Contracts;

use App\Services\Contact\Exceptions\RateLimitExceededException;

interface RateLimiterInterface
{
    /**
     * @throws RateLimitExceededException
     */
    public function assertAllowed(string $key): void;
}
