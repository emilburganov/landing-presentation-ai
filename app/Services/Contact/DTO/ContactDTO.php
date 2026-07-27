<?php

namespace App\Services\Contact\DTO;

readonly class ContactDTO
{
    public function __construct(
        public string $name,
        public string $phone,
        public string $email,
        public string $comment,
    )
    {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            phone: $data['phone'],
            email: $data['email'],
            comment: $data['comment'],
        );
    }
}
