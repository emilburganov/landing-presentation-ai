<?php

namespace App\Services\Contact;

use App\Http\Controllers\API\ContactController;
use App\Services\Contact\Analysis\AiCommentAnalyzer;
use App\Services\Contact\Analysis\CommentAnalyzer;
use App\Services\Contact\Contracts\ContactHandlerInterface;
use App\Services\Contact\Contracts\RateLimiterInterface;
use App\Services\Contact\RateLimiting\ContactRateLimiter;
use Illuminate\Cache\RateLimiter as CacheRateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

class ContactServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RateLimiterInterface::class, function ($app) {
            return new ContactRateLimiter(
                limiter: $app->make(CacheRateLimiter::class),
                maxAttempts: (int) config('contact.rate_limit_max'),
                decaySeconds: (int) config('contact.rate_limit_window'),
            );
        });

        $this->app->singleton(CommentAnalyzer::class, AiCommentAnalyzer::class);
        $this->app->singleton(ContactHandlerInterface::class, ContactService::class);

        $this->app->when(ContactController::class)
            ->needs(LoggerInterface::class)
            ->give(fn () => Log::channel('contact'));
    }
}
