<?php

namespace App\Services\Contact\Analysis;

use App\Services\AI\Contracts\CommentAnalyzerInterface as AiCommentAnalyzerInterface;
use App\Services\AI\Exceptions\AIException;
use App\Services\Contact\Exceptions\CommentAnalysisFailedException;

/**
 * Anti-corruption layer: единственная точка контакта Contact → AI.
 */
readonly class AiCommentAnalyzer implements CommentAnalyzer
{
    public function __construct(
        private AiCommentAnalyzerInterface $aiAnalyzer,
    )
    {
    }

    /**
     * @throws CommentAnalysisFailedException
     */
    public function analyze(string $comment): CommentAnalysis
    {
        try {
            $result = $this->aiAnalyzer->analyzeComment($comment);
        } catch (AIException $e) {
            throw new CommentAnalysisFailedException(
                message: $e->getMessage(),
                statusCode: $e->statusCode ?? 500,
                raw: $e->raw,
            );
        }

        return new CommentAnalysis(
            sentiment: $result->sentiment,
            type: $result->type,
            usedAi: $result->usedAi,
            autoReply: $result->autoReply,
        );
    }
}
