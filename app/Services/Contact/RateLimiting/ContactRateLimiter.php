<?php

namespace App\Services\Contact\RateLimiting;

use App\Services\Contact\Contracts\RateLimiterInterface;
use App\Services\Contact\Exceptions\RateLimitExceededException;
use Illuminate\Cache\RateLimiter as CacheRateLimiter;
use Psr\Log\LoggerInterface;

readonly class ContactRateLimiter implements RateLimiterInterface
{
    private const string KEY_PREFIX = 'contact:';

    public function __construct(
        private CacheRateLimiter $limiter,
        private int              $maxAttempts,
        private int              $decaySeconds,
        private LoggerInterface  $logger,
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
            $retryAfter = $this->limiter->availableIn($rateLimitKey);

            $this->logger->warning('contact.rate_limit.exceeded', [
                'key' => $key,
                'max_attempts' => $this->maxAttempts,
                'retry_after' => $retryAfter,
            ]);

            throw new RateLimitExceededException(
                message: 'Too many requests. Try again later.',
                retryAfter: $retryAfter,
            );
        }

        $this->limiter->hit($rateLimitKey, $this->decaySeconds);

        $this->logger->debug('contact.rate_limit.hit', [
            'key' => $key,
            'attempts' => $this->limiter->attempts($rateLimitKey),
            'max_attempts' => $this->maxAttempts,
        ]);
    }
}
