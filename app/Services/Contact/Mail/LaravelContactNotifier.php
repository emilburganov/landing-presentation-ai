<?php

namespace App\Services\Contact\Mail;

use App\Services\Contact\Analysis\CommentAnalysis;
use App\Services\Contact\DTO\ContactDTO;
use App\Services\Contact\Exceptions\ContactNotificationFailedException;
use App\Services\Contact\Mail\Mailables\OwnerContactReceivedMail;
use App\Services\Contact\Mail\Mailables\UserContactCopyMail;
use Illuminate\Contracts\Mail\Mailer;
use Psr\Log\LoggerInterface;
use Throwable;

readonly class LaravelContactNotifier implements ContactNotifierInterface
{
    public function __construct(
        private Mailer          $mailer,
        private string          $ownerEmail,
        private string          $mailerName,
        private LoggerInterface $logger,
    )
    {
    }

    /**
     * @throws ContactNotificationFailedException
     */
    public function notify(ContactDTO $contact, CommentAnalysis $analysis): void
    {
        $this->logger->info('contact.mail.started', [
            'mailer' => $this->mailerName,
            'owner_email' => $this->ownerEmail,
            'user_email' => $contact->email,
        ]);

        if (in_array($this->mailerName, ['log', 'array'], true)) {
            $this->logger->warning('contact.mail.not_delivered_to_inbox', [
                'mailer' => $this->mailerName,
                'hint' => 'MAIL_MAILER=log|array only writes messages to logs/memory. Set MAIL_MAILER=smtp (or ses/postmark/resend) to deliver real emails.',
            ]);
        }

        $failures = [];

        $failures['owner'] = $this->sendOwnerMail($contact, $analysis);
        $failures['user'] = $this->sendUserCopyMail($contact, $analysis);

        $failures = array_filter($failures);

        if ($failures !== []) {
            $this->logger->error('contact.mail.failed', [
                'mailer' => $this->mailerName,
                'failures' => $failures,
            ]);

            throw new ContactNotificationFailedException(
                message: 'Failed to send contact notification emails.',
                raw: [
                    'mailer' => $this->mailerName,
                    'failures' => $failures,
                ],
            );
        }

        $this->logger->info('contact.mail.succeeded', [
            'mailer' => $this->mailerName,
            'owner_email' => $this->ownerEmail,
            'user_email' => $contact->email,
        ]);
    }

    /**
     * @return array{to: string, error: string}|null
     */
    private function sendOwnerMail(ContactDTO $contact, CommentAnalysis $analysis): ?array
    {
        try {
            $this->logger->info('contact.mail.owner.sending', [
                'to' => $this->ownerEmail,
            ]);

            $this->mailer->to($this->ownerEmail)->send(
                new OwnerContactReceivedMail($contact, $analysis),
            );

            $this->logger->info('contact.mail.owner.sent', [
                'to' => $this->ownerEmail,
            ]);

            return null;
        } catch (Throwable $e) {
            $this->logger->error('contact.mail.owner.failed', [
                'to' => $this->ownerEmail,
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            return [
                'to' => $this->ownerEmail,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return array{to: string, error: string}|null
     */
    private function sendUserCopyMail(ContactDTO $contact, CommentAnalysis $analysis): ?array
    {
        try {
            $this->logger->info('contact.mail.user.sending', [
                'to' => $contact->email,
            ]);

            $this->mailer->to($contact->email, $contact->name)->send(
                new UserContactCopyMail($contact, $analysis),
            );

            $this->logger->info('contact.mail.user.sent', [
                'to' => $contact->email,
            ]);

            return null;
        } catch (Throwable $e) {
            $this->logger->error('contact.mail.user.failed', [
                'to' => $contact->email,
                'error' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            return [
                'to' => $contact->email,
                'error' => $e->getMessage(),
            ];
        }
    }
}
