<?php

namespace App\Services\Contact;

use App\Services\Contact\DTO\ContactDTO;
use App\Services\Contact\DTO\ContactResultDTO;

class ContactService
{
    public function handleContact(ContactDTO $contactDTO): ContactResultDTO
    {
        return new ContactResultDTO(
            message: '',
            sentiment: '',
            type: '',
            aiUsed: false,
        );
    }
}
