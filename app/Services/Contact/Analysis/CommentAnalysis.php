<?php

namespace App\Services\Contact\Analysis;

readonly class CommentAnalysis
{
    public function __construct(
        public string $sentiment,
        public string $type,
        public bool $usedAi,
        public ?string $autoReply = null,
    ) {
    }
}
