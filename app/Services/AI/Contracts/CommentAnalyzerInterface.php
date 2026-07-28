<?php

namespace App\Services\AI\Contracts;

use App\Services\AI\DTO\CommentAnalysisResultDTO;
use App\Services\AI\Exceptions\AIException;

interface CommentAnalyzerInterface
{
    /**
     * @throws AIException
     */
    public function analyzeComment(string $comment): CommentAnalysisResultDTO;
}
