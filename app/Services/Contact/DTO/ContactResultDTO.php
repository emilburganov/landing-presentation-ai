<?php

namespace App\Services\Contact\DTO;

use App\Services\AI\DTO\CommentAnalysisResultDTO;

readonly class ContactResultDTO
{
    public function __construct(
        public bool    $success,
        public string  $message,
        public ?string $sentiment = null,
        public ?string $type = null,
        public ?bool   $aiUsed = null,
        public ?string $error = null,
    )
    {
    }

    public static function accepted(CommentAnalysisResultDTO $analysis): self
    {
        return new self(
            success: true,
            message: 'Contact request accepted.',
            sentiment: $analysis->sentiment,
            type: $analysis->type,
            aiUsed: $analysis->usedAi,
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'message' => $this->message,
            'sentiment' => $this->sentiment,
            'type' => $this->type,
            'ai_used' => $this->aiUsed,
            'error' => $this->error,
        ];
    }
}
