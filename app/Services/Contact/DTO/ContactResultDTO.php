<?php

namespace App\Services\Contact\DTO;

readonly class ContactResultDTO
{
    public function __construct(
        public bool    $success,
        public string  $message,
        public ?string $sentiment = null,
        public ?string $type = null,
        public ?bool   $aiUsed = null,
        public ?string  $error = null,
    )
    {
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

