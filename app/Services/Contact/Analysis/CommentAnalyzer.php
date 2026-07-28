<?php

namespace App\Services\Contact\Analysis;

use App\Services\Contact\Exceptions\CommentAnalysisFailedException;

interface CommentAnalyzer
{
    /**
     * @throws CommentAnalysisFailedException
     */
    public function analyze(string $comment): CommentAnalysis;
}
