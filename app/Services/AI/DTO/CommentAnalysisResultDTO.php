<?php

namespace App\Services\AI\DTO;

readonly class CommentAnalysisResultDTO
{
    public function __construct(
        public string  $sentiment,
        public string  $type,
        public ?string $autoReply,
        public bool    $usedAi,
        public ?string $aiError,
    )
    {
    }
}

