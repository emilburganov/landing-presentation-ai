<?php

namespace App\Services\Contact;

use App\Services\Contact\Exceptions\RateLimitExceededException;
use Illuminate\Support\Facades\Cache;

class RateLimiter
{
    public function assertAllowed(string $key): void
    {
        $max = config('contact.rate_limit_max');
        $window = config('contact.rate_limit_window');

        $cacheKey = "contact_rate_limit:$key";

        $count = Cache::increment($cacheKey);

        // Если ключ только что создан — ставим TTL
        if ($count === 1) {
            Cache::put($cacheKey, $count, $window);
        }

        // Проверка лимита
        if ($count > $max) {
            throw new RateLimitExceededException(
                message: 'Too many requests. Try again later.',
                retryAfter: $window
            );
        }
    }
}
