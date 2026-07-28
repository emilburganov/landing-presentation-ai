<?php

namespace App\Services\Contact;

use App\Services\Contact\Contracts\ContactHandlerInterface;
use App\Services\Contact\Contracts\RateLimiterInterface;
use App\Services\Contact\RateLimiting\ContactRateLimiter;
use Illuminate\Cache\RateLimiter as CacheRateLimiter;
use Illuminate\Support\ServiceProvider;

class ContactServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RateLimiterInterface::class, function ($app) {
            return new ContactRateLimiter(
                limiter: $app->make(CacheRateLimiter::class),
                maxAttempts: (int)config('contact.rate_limit_max'),
                decaySeconds: (int)config('contact.rate_limit_window'),
            );
        });

        $this->app->singleton(ContactHandlerInterface::class, ContactService::class);
    }
}
