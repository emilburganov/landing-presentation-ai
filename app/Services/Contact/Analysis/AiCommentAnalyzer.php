<?php

namespace App\Services\Contact\Analysis;

use App\Services\AI\Contracts\CommentAnalyzerInterface as AiCommentAnalyzerInterface;
use App\Services\AI\Exceptions\AIException;
use App\Services\Contact\Exceptions\CommentAnalysisFailedException;
use Psr\Log\LoggerInterface;

/**
 * Anti-corruption layer: единственная точка контакта Contact → AI.
 */
readonly class AiCommentAnalyzer implements CommentAnalyzer
{
    public function __construct(
        private AiCommentAnalyzerInterface $aiAnalyzer,
        private LoggerInterface            $logger,
    )
    {
    }

    /**
     * @throws CommentAnalysisFailedException
     */
    public function analyze(string $comment): CommentAnalysis
    {
        $this->logger->info('contact.analysis.started', [
            'comment_length' => mb_strlen($comment),
        ]);

        try {
            $result = $this->aiAnalyzer->analyzeComment($comment);
        } catch (AIException $e) {
            $this->logger->error('contact.analysis.failed', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'status_code' => $e->statusCode,
                'raw' => $e->raw,
            ]);

            throw new CommentAnalysisFailedException(
                message: $e->getMessage(),
                statusCode: $e->statusCode ?? 500,
                raw: $e->raw,
            );
        }

        $analysis = new CommentAnalysis(
            sentiment: $result->sentiment,
            type: $result->type,
            usedAi: $result->usedAi,
            autoReply: $result->autoReply,
        );

        $this->logger->info('contact.analysis.succeeded', [
            'sentiment' => $analysis->sentiment,
            'type' => $analysis->type,
            'ai_used' => $analysis->usedAi,
        ]);

        return $analysis;
    }
}
