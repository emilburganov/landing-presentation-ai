<?php

namespace App\Services\Contact;

use App\Services\Contact\DTO\ContactMetricsDTO;
use App\Services\Contact\Repositories\ContactRepositoryInterface;

readonly class ContactMetricsService
{
    public function __construct(
        private ContactRepositoryInterface $contacts,
    )
    {
    }

    public function getMetrics(): ContactMetricsDTO
    {
        return $this->contacts->metrics();
    }
}
