<?php

namespace App\Services\AI\Groq;

use App\Services\AI\Contracts\CommentAnalyzerInterface;
use App\Services\AI\DTO\CommentAnalysisResultDTO;
use App\Services\AI\Exceptions\AIException;
use App\Services\AI\Support\CommentAnalysisParser;
use App\Services\AI\Support\CommentAnalysisPrompt;
use App\Services\AI\Support\GuzzleExceptionMapper;
use Throwable;

readonly class GroqCommentAnalyzer implements CommentAnalyzerInterface
{
    public function __construct(
        private GroqApiClient $api,
        private CommentAnalysisPrompt $prompt,
        private CommentAnalysisParser $parser,
        private GuzzleExceptionMapper $exceptionMapper,
    ) {
    }

    /**
     * @throws AIException
     */
    public function analyzeComment(string $comment): CommentAnalysisResultDTO
    {
        try {
            $response = $this->api->chat(
                system: $this->prompt->system(),
                user: $comment,
            );

            return $this->parser->parse($response);
        } catch (AIException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw $this->exceptionMapper->map($e);
        }
    }
}
