<?php

namespace App\Services\Contact;

use App\Services\Contact\Analysis\CommentAnalysis;
use App\Services\Contact\Analysis\CommentAnalyzer;
use App\Services\Contact\Contracts\ContactHandlerInterface;
use App\Services\Contact\Contracts\RateLimiterInterface;
use App\Services\Contact\DTO\ContactDTO;
use App\Services\Contact\DTO\ContactResultDTO;
use App\Services\Contact\Exceptions\CommentAnalysisFailedException;
use App\Services\Contact\Exceptions\ContactNotificationFailedException;
use App\Services\Contact\Exceptions\RateLimitExceededException;
use App\Services\Contact\Mail\ContactNotifierInterface;
use Psr\Log\LoggerInterface;
use Throwable;

readonly class ContactService implements ContactHandlerInterface
{
    public function __construct(
        private RateLimiterInterface     $rateLimiter,
        private CommentAnalyzer          $commentAnalyzer,
        private ContactNotifierInterface $notifier,
        private LoggerInterface          $logger,
    )
    {
    }

    /**
     * @throws RateLimitExceededException
     * @throws CommentAnalysisFailedException
     * @throws ContactNotificationFailedException
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

            $this->sendNotifications($contactDTO, $analysis);

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

    /**
     * @throws ContactNotificationFailedException
     */
    private function sendNotifications(ContactDTO $contactDTO, CommentAnalysis $analysis): void
    {
        try {
            $this->notifier->notify($contactDTO, $analysis);
        } catch (ContactNotificationFailedException $e) {
            $this->logger->error('contact.handle.mail_failed', [
                'email' => $contactDTO->email,
                'message' => $e->getMessage(),
                'raw' => $e->raw,
            ]);

            throw $e;
        } catch (Throwable $e) {
            $this->logger->error('contact.handle.mail_failed', [
                'email' => $contactDTO->email,
                'message' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            throw new ContactNotificationFailedException(
                message: 'Failed to send contact notification emails.',
                raw: [
                    'message' => $e->getMessage(),
                    'exception' => $e::class,
                ],
            );
        }
    }
}
