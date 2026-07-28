<?php

namespace App\Services\Contact\Mail;

use App\Services\Contact\Analysis\CommentAnalysis;
use App\Services\Contact\DTO\ContactDTO;
use App\Services\Contact\Exceptions\ContactNotificationFailedException;

interface ContactNotifierInterface
{
    /**
     * @throws ContactNotificationFailedException
     */
    public function notify(ContactDTO $contact, CommentAnalysis $analysis): void;
}
