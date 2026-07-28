<?php

namespace App\Services\Contact\Repositories;

use App\Models\Contact;
use App\Services\Contact\Analysis\CommentAnalysis;
use App\Services\Contact\DTO\ContactDTO;
use App\Services\Contact\DTO\ContactMetricsDTO;

interface ContactRepositoryInterface
{
    public function create(ContactDTO $contact, CommentAnalysis $analysis): Contact;

    public function metrics(): ContactMetricsDTO;
}
