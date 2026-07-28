<?php

namespace App\Services\AI\Support;

use App\Services\AI\Contracts\ResponseValidatorInterface;
use App\Services\AI\DTO\CommentAnalysisResultDTO;
use App\Services\AI\Enums\CommentType;
use App\Services\AI\Enums\Sentiment;
use App\Services\AI\Exceptions\AIInvalidResponseException;
use Psr\Log\LoggerInterface;

readonly class CommentAnalysisParser
{
    public function __construct(
        private ResponseValidatorInterface $validator,
        private LoggerInterface            $logger,
    )
    {
    }

    /**
     * @throws AIInvalidResponseException
     */
    public function parse(array $apiResponse): CommentAnalysisResultDTO
    {
        $content = $apiResponse['choices'][0]['message']['content'] ?? null;

        if (!$content) {
            $this->logger->warning('ai.parse.no_content', [
                'response_keys' => array_keys($apiResponse),
            ]);
            throw new AIInvalidResponseException(raw: $apiResponse);
        }

        $json = json_decode($content, true);

        if (!is_array($json)) {
            $this->logger->warning('ai.parse.invalid_json', [
                'content_preview' => mb_substr((string)$content, 0, 200),
            ]);
            throw new AIInvalidResponseException(raw: ['content' => $content]);
        }

        try {
            $this->validator->validate($json);
        } catch (AIInvalidResponseException $e) {
            $this->logger->warning('ai.parse.invalid_schema', [
                'content' => $json,
                'errors' => $e->raw['errors'] ?? null,
            ]);
            throw $e;
        }

        $result = new CommentAnalysisResultDTO(
            sentiment: Sentiment::from($json['sentiment'])->value,
            type: CommentType::from($json['type'])->value,
            autoReply: $json['auto_reply'] ?? null,
            usedAi: true,
            aiError: null,
        );

        $this->logger->info('ai.parse.succeeded', [
            'sentiment' => $result->sentiment,
            'type' => $result->type,
            'has_auto_reply' => $result->autoReply !== null,
        ]);

        return $result;
    }
}
