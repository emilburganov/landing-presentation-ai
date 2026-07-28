<?php

namespace App\Services\Contact\Contracts;

use App\Services\Contact\DTO\ContactDTO;
use App\Services\Contact\DTO\ContactResultDTO;
use App\Services\Contact\Exceptions\CommentAnalysisFailedException;
use App\Services\Contact\Exceptions\ContactNotificationFailedException;
use App\Services\Contact\Exceptions\RateLimitExceededException;

interface ContactHandlerInterface
{
    /**
     * @throws RateLimitExceededException
     * @throws CommentAnalysisFailedException
     * @throws ContactNotificationFailedException
     */
    public function handleContact(ContactDTO $contactDTO): ContactResultDTO;
}
