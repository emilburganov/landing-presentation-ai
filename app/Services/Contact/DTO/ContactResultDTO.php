<?php

namespace App\Services\Contact\DTO;

readonly class ContactResultDTO
{
    public function __construct(
        public string $message,
        public string $sentiment,
        public string $type,
        public bool   $aiUsed,
    )
    {
    }

    public function toArray(): array
    {
        return [
            'message' => $this->message,
            'sentiment' => $this->sentiment,
            'type' => $this->type,
            'ai_used' => $this->aiUsed,
        ];
    }
}

