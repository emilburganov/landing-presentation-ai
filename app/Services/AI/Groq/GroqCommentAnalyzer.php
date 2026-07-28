<?php

namespace App\Services\AI\Groq;

use App\Services\AI\Contracts\CommentAnalyzerInterface;
use App\Services\AI\DTO\CommentAnalysisResultDTO;
use App\Services\AI\Exceptions\AIException;
use App\Services\AI\Support\CommentAnalysisParser;
use App\Services\AI\Support\CommentAnalysisPrompt;
use App\Services\AI\Support\GuzzleExceptionMapper;
use Psr\Log\LoggerInterface;
use Throwable;

readonly class GroqCommentAnalyzer implements CommentAnalyzerInterface
{
    public function __construct(
        private GroqApiClient         $api,
        private CommentAnalysisPrompt $prompt,
        private CommentAnalysisParser $parser,
        private GuzzleExceptionMapper $exceptionMapper,
        private LoggerInterface       $logger,
    )
    {
    }

    /**
     * @throws AIException
     */
    public function analyzeComment(string $comment): CommentAnalysisResultDTO
    {
        $this->logger->info('ai.analyze.started', [
            'provider' => 'groq',
            'comment_length' => mb_strlen($comment),
        ]);

        try {
            $response = $this->api->chat(
                system: $this->prompt->system(),
                user: $comment,
            );

            $result = $this->parser->parse($response);

            $this->logger->info('ai.analyze.succeeded', [
                'provider' => 'groq',
                'sentiment' => $result->sentiment,
                'type' => $result->type,
            ]);

            return $result;
        } catch (AIException $e) {
            $this->logger->error('ai.analyze.failed', [
                'provider' => 'groq',
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'status_code' => $e->statusCode,
                'raw' => $e->raw,
            ]);

            throw $e;
        } catch (Throwable $e) {
            $mapped = $this->exceptionMapper->map($e);

            $this->logger->error('ai.analyze.failed', [
                'provider' => 'groq',
                'exception' => $mapped::class,
                'message' => $mapped->getMessage(),
                'status_code' => $mapped->statusCode,
                'raw' => $mapped->raw,
            ]);

            throw $mapped;
        }
    }
}
