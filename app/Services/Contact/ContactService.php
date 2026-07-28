<?php

namespace App\Services\Contact;

use App\Services\AI\Contracts\CommentAnalyzerInterface;
use App\Services\AI\Exceptions\AIException;
use App\Services\Contact\DTO\ContactDTO;
use App\Services\Contact\DTO\ContactResultDTO;
use App\Services\Contact\Exceptions\RateLimitExceededException;

readonly class ContactService
{
    public function __construct(
        private RateLimiter $rateLimiter,
        private CommentAnalyzerInterface $commentAnalyzer,
    )
    {
    }

    /**
     * @throws RateLimitExceededException
     * @throws AIException
     */
    public function handleContact(ContactDTO $contactDTO): ContactResultDTO
    {
        $this->rateLimiter->assertAllowed($contactDTO->email);
        $analysis = $this->commentAnalyzer->analyzeComment($contactDTO->comment);

        return new ContactResultDTO(
            success: true,
            message: 'Contact request accepted.',
            sentiment: $analysis->sentiment,
            type: $analysis->type,
            aiUsed: true,
        );
    }
}
