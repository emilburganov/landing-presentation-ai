<?php

namespace App\Services\Contact\Contracts;

use App\Services\Contact\DTO\ContactDTO;
use App\Services\Contact\DTO\ContactResultDTO;
use App\Services\Contact\Exceptions\CommentAnalysisFailedException;
use App\Services\Contact\Exceptions\RateLimitExceededException;

interface ContactHandlerInterface
{
    /**
     * @throws RateLimitExceededException
     * @throws CommentAnalysisFailedException
     */
    public function handleContact(ContactDTO $contactDTO): ContactResultDTO;
}
