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
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @throws AIInvalidResponseException
     */
    public function parse(array $apiResponse): CommentAnalysisResultDTO
    {
        $content = $apiResponse['choices'][0]['message']['content'] ?? null;

        if (!$content) {
            $this->logger->warning('ai_no_content', ['response' => $apiResponse]);
            throw new AIInvalidResponseException(raw: $apiResponse);
        }

        $json = json_decode($content, true);

        if (!is_array($json)) {
            $this->logger->warning('ai_json_not_array', ['content' => $content]);
            throw new AIInvalidResponseException(raw: ['content' => $content]);
        }

        $this->validator->validate($json);

        return new CommentAnalysisResultDTO(
            sentiment: Sentiment::from($json['sentiment'])->value,
            type: CommentType::from($json['type'])->value,
            autoReply: $json['auto_reply'] ?? null,
            usedAi: true,
            aiError: null,
        );
    }
}
