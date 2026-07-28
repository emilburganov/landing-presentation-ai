<?php

namespace App\Services\Contact;

use App\Services\Contact\Analysis\CommentAnalyzer;
use App\Services\Contact\Contracts\ContactHandlerInterface;
use App\Services\Contact\Contracts\RateLimiterInterface;
use App\Services\Contact\DTO\ContactDTO;
use App\Services\Contact\DTO\ContactResultDTO;
use App\Services\Contact\Exceptions\CommentAnalysisFailedException;
use App\Services\Contact\Exceptions\RateLimitExceededException;
use Psr\Log\LoggerInterface;

readonly class ContactService implements ContactHandlerInterface
{
    public function __construct(
        private RateLimiterInterface $rateLimiter,
        private CommentAnalyzer      $commentAnalyzer,
        private LoggerInterface      $logger,
    )
    {
    }

    /**
     * @throws RateLimitExceededException
     * @throws CommentAnalysisFailedException
     */
    public function handleContact(ContactDTO $contactDTO): ContactResultDTO
    {
        $this->logger->info('contact.handle.started', [
            'email' => $contactDTO->email,
            'comment_length' => mb_strlen($contactDTO->comment),
        ]);

        try {
            $this->rateLimiter->assertAllowed($contactDTO->email);

            $analysis = $this->commentAnalyzer->analyze($contactDTO->comment);

            $result = ContactResultDTO::accepted($analysis);

            $this->logger->info('contact.handle.succeeded', [
                'email' => $contactDTO->email,
                'sentiment' => $result->sentiment,
                'type' => $result->type,
                'ai_used' => $result->aiUsed,
            ]);

            return $result;
        } catch (RateLimitExceededException $e) {
            $this->logger->warning('contact.handle.rate_limited', [
                'email' => $contactDTO->email,
                'retry_after' => $e->retryAfter,
            ]);

            throw $e;
        } catch (CommentAnalysisFailedException $e) {
            $this->logger->error('contact.handle.analysis_failed', [
                'email' => $contactDTO->email,
                'message' => $e->getMessage(),
                'status_code' => $e->statusCode,
                'raw' => $e->raw,
            ]);

            throw $e;
        }
    }
}
