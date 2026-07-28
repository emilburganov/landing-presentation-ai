<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\CommentAnalyzerInterface;
use App\Services\AI\Contracts\ResponseValidatorInterface;
use App\Services\AI\Groq\GroqApiClient;
use App\Services\AI\Groq\GroqCommentAnalyzer;
use App\Services\AI\Support\CommentAnalysisParser;
use App\Services\AI\Support\CommentAnalysisPrompt;
use App\Services\AI\Support\GuzzleExceptionMapper;
use App\Services\AI\Validation\JsonSchemaResponseValidator;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AIServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ResponseValidatorInterface::class, function () {
            return new JsonSchemaResponseValidator(
                schemaPath: base_path('app/Services/AI/Schemas/comment-analysis.json'),
            );
        });

        $this->app->singleton(CommentAnalysisPrompt::class);
        $this->app->singleton(GuzzleExceptionMapper::class);

        $this->app->singleton(CommentAnalysisParser::class, function ($app) {
            return new CommentAnalysisParser(
                validator: $app->make(ResponseValidatorInterface::class),
                logger: Log::channel('ai'),
            );
        });

        $this->app->singleton(CommentAnalyzerInterface::class, function ($app) {
            return match (config('ai.provider')) {
                'groq' => $this->makeGroqAnalyzer($app),
                default => throw new InvalidArgumentException(
                    'Unsupported AI provider: ' . config('ai.provider'),
                ),
            };
        });
    }

    private function makeGroqAnalyzer($app): GroqCommentAnalyzer
    {
        $api = new GroqApiClient(
            http: new Client(),
            url: config('ai.groq.url'),
            apiKey: (string) config('ai.groq.key'),
            model: (string) config('ai.groq.model'),
        );

        return new GroqCommentAnalyzer(
            api: $api,
            prompt: $app->make(CommentAnalysisPrompt::class),
            parser: $app->make(CommentAnalysisParser::class),
            exceptionMapper: $app->make(GuzzleExceptionMapper::class),
            logger: Log::channel('ai'),
        );
    }
}

