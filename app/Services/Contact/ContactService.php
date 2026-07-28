<?php

namespace App\Services\Contact;

use App\Services\Contact\Analysis\CommentAnalyzer;
use App\Services\Contact\Contracts\ContactHandlerInterface;
use App\Services\Contact\Contracts\RateLimiterInterface;
use App\Services\Contact\DTO\ContactDTO;
use App\Services\Contact\DTO\ContactResultDTO;
use App\Services\Contact\Exceptions\CommentAnalysisFailedException;
use App\Services\Contact\Exceptions\RateLimitExceededException;

readonly class ContactService implements ContactHandlerInterface
{
    public function __construct(
        private RateLimiterInterface $rateLimiter,
        private CommentAnalyzer      $commentAnalyzer,
    )
    {
    }

    /**
     * @throws RateLimitExceededException
     * @throws CommentAnalysisFailedException
     */
    public function handleContact(ContactDTO $contactDTO): ContactResultDTO
    {
        $this->rateLimiter->assertAllowed($contactDTO->email);

        $analysis = $this->commentAnalyzer->analyze($contactDTO->comment);

        return ContactResultDTO::accepted($analysis);
    }
}
