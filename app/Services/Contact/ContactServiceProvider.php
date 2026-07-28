<?php

namespace App\Services\Contact;

use App\Services\AI\Contracts\CommentAnalyzerInterface;
use App\Services\Contact\Analysis\AiCommentAnalyzer;
use App\Services\Contact\Analysis\CommentAnalyzer;
use App\Services\Contact\Contracts\ContactHandlerInterface;
use App\Services\Contact\Contracts\RateLimiterInterface;
use App\Services\Contact\Mail\ContactNotifierInterface;
use App\Services\Contact\Mail\LaravelContactNotifier;
use App\Services\Contact\RateLimiting\ContactRateLimiter;
use Illuminate\Cache\RateLimiter as CacheRateLimiter;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class ContactServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(RateLimiterInterface::class, function ($app) {
            return new ContactRateLimiter(
                limiter: $app->make(CacheRateLimiter::class),
                maxAttempts: (int)config('contact.rate_limit_max'),
                decaySeconds: (int)config('contact.rate_limit_window'),
                logger: Log::channel('contact'),
            );
        });

        $this->app->singleton(CommentAnalyzer::class, function ($app) {
            return new AiCommentAnalyzer(
                aiAnalyzer: $app->make(CommentAnalyzerInterface::class),
                logger: Log::channel('contact'),
            );
        });

        $this->app->singleton(ContactNotifierInterface::class, function ($app) {
            $ownerEmail = config('contact.owner_email');

            if (!is_string($ownerEmail) || $ownerEmail === '') {
                throw new InvalidArgumentException('CONTACT_OWNER_EMAIL is not configured.');
            }

            return new LaravelContactNotifier(
                mailer: $app->make(Mailer::class),
                ownerEmail: $ownerEmail,
                mailerName: (string)config('mail.default'),
                logger: Log::channel('contact'),
            );
        });

        $this->app->singleton(ContactHandlerInterface::class, function ($app) {
            return new ContactService(
                rateLimiter: $app->make(RateLimiterInterface::class),
                commentAnalyzer: $app->make(CommentAnalyzer::class),
                notifier: $app->make(ContactNotifierInterface::class),
                logger: Log::channel('contact'),
            );
        });
    }
}
