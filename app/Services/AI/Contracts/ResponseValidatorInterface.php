<?php

namespace App\Services\AI\Contracts;

use App\Services\AI\Exceptions\AIInvalidResponseException;

interface ResponseValidatorInterface
{
    /**
     * @throws AIInvalidResponseException
     */
    public function validate(array $json): void;
}
