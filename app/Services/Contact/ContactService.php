<?php

namespace App\Services\Contact;

use App\Services\AI\Contracts\CommentAnalyzerInterface;
use App\Services\AI\Exceptions\AIException;
use App\Services\Contact\Contracts\ContactHandlerInterface;
use App\Services\Contact\Contracts\RateLimiterInterface;
use App\Services\Contact\DTO\ContactDTO;
use App\Services\Contact\DTO\ContactResultDTO;
use App\Services\Contact\Exceptions\RateLimitExceededException;

readonly class ContactService implements ContactHandlerInterface
{
    public function __construct(
        private RateLimiterInterface     $rateLimiter,
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

        return ContactResultDTO::accepted($analysis);
    }
}
