<?php

namespace App\Services\Contact\RateLimiting;

use App\Services\Contact\Contracts\RateLimiterInterface;
use App\Services\Contact\Exceptions\RateLimitExceededException;
use Illuminate\Cache\RateLimiter as CacheRateLimiter;

readonly class ContactRateLimiter implements RateLimiterInterface
{
    private const string KEY_PREFIX = 'contact:';

    public function __construct(
        private CacheRateLimiter $limiter,
        private int              $maxAttempts,
        private int              $decaySeconds,
    )
    {
    }

    /**
     * @throws RateLimitExceededException
     */
    public function assertAllowed(string $key): void
    {
        $rateLimitKey = self::KEY_PREFIX . $key;

        if ($this->limiter->tooManyAttempts($rateLimitKey, $this->maxAttempts)) {
            throw new RateLimitExceededException(
                message: 'Too many requests. Try again later.',
                retryAfter: $this->limiter->availableIn($rateLimitKey),
            );
        }

        $this->limiter->hit($rateLimitKey, $this->decaySeconds);
    }
}
