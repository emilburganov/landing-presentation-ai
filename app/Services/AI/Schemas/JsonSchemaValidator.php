<?php

namespace App\Services\AI\Schemas;

use League\JsonGuard\Validator;
use League\JsonGuard\Exceptions\ValidationException;

class JsonSchemaValidator
{
    private object $schema;

    public function __construct()
    {
        $this->schema = json_decode(
            file_get_contents(base_path('app/Services/AI/schemas/comment-analysis.json'))
        );
    }

    public function validate(array $json): void
    {
        $validator = new Validator((object)$json, $this->schema);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }
    }
}
