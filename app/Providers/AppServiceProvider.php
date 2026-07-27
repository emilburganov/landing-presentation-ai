<?php

namespace App\Providers;

use App\Services\AI\AIClientInterface;
use App\Services\AI\GroqClient;
use App\Services\AI\Schemas\JsonSchemaValidator;
use GuzzleHttp\Client;
use Illuminate\Support\ServiceProvider;
use Log;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AIClientInterface::class, function () {
            return new GroqClient(
                http: new Client(),
                logger: Log::channel('contact'),
                validator: new JsonSchemaValidator(),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
