<?php

namespace App\Services\AI;

use App\Services\AI\DTO\CommentAnalysisResultDTO;
use App\Services\AI\Exceptions\AIException;

interface AIClientInterface
{
    /**
     * @throws AIException
     */
    public function analyzeComment(string $comment): CommentAnalysisResultDTO;
}
