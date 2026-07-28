<?php

namespace App\Services\AI\Validation;

use App\Services\AI\Contracts\ResponseValidatorInterface;
use App\Services\AI\Exceptions\AIInvalidResponseException;
use JsonSchema\Validator;

readonly class JsonSchemaResponseValidator implements ResponseValidatorInterface
{
    private object $schema;

    public function __construct(string $schemaPath)
    {
        $this->schema = json_decode(file_get_contents($schemaPath));
    }

    /**
     * @throws AIInvalidResponseException
     */
    public function validate(array $json): void
    {
        $data = json_decode(json_encode($json));

        $validator = new Validator();
        $validator->validate($data, $this->schema);

        if (!$validator->isValid()) {
            throw new AIInvalidResponseException(raw: [
                'content' => $json,
                'errors' => $validator->getErrors(),
            ]);
        }
    }
}
