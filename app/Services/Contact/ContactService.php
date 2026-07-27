<?php

namespace App\Services\Contact;

use App\Services\Contact\DTO\ContactDTO;
use App\Services\Contact\DTO\ContactResultDTO;

readonly class ContactService
{
    public function __construct(
        private RateLimiter $rateLimiter,
    )
    {
    }

    public function handleContact(ContactDTO $contactDTO): ContactResultDTO
    {
        $this->rateLimiter->assertAllowed($contactDTO->email);

        return new ContactResultDTO(
            message: '',
            sentiment: '',
            type: '',
            aiUsed: false,
        );
    }
}
