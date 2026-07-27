<?php

namespace App\Services\AI;

use App\Services\AI\DTO\CommentAnalysisResultDTO;
use App\Services\AI\Exceptions\AIConnectionException;
use App\Services\AI\Exceptions\AIException;
use App\Services\AI\Exceptions\AIServerException;
use App\Services\AI\Exceptions\AIUnauthorizedException;
use App\Services\AI\Schemas\JsonSchemaValidator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use Psr\Log\LoggerInterface;
use Throwable;

readonly class GroqClient implements AIClientInterface
{
    private const int TIMEOUT = 10;

    public function __construct(
        private Client              $http,
        private LoggerInterface     $logger,
        private JsonSchemaValidator $validator,
    )
    {
    }

    /**
     * @throws AIException
     */
    public function analyzeComment(string $comment): CommentAnalysisResultDTO
    {
        try {
            $response = $this->http->post(config('ai.groq.url'), [
                'headers' => [
                    'Authorization' => 'Bearer ' . config('ai.groq.key'),
                ],
                'json' => [
                    'model' => config('ai.groq.model'),
                    'messages' => [
                        ['role' => 'system', 'content' => $this->systemPrompt()],
                        ['role' => 'user', 'content' => $comment],
                    ],
                ],
                'timeout' => self::TIMEOUT,
            ]);


            $data = json_decode($response->getBody()->getContents(), true);
            $content = $data['choices'][0]['message']['content'] ?? '{}';

            if (!$content) {
                $this->logger->warning('ai_no_content', ['content' => $content]);
                throw new AIInvalidResponseException(raw: $data);
            }

            $json = json_decode($content, true);
            if (!is_array($json)) {
                $this->logger->warning('ai_json_not_array', ['content' => $content]);
                throw new AIInvalidResponseException(raw: ['content' => $content]);
            }

            // TODO: validate deeper json
            if (!isset($json['sentiment']) || !isset($json['type'])) {
                $this->logger->warning('ai_invalid_json', ['content' => $content]);
                throw new AIInvalidResponseException(raw: $json);
            }

            return new CommentAnalysisResultDTO(
                sentiment: $json['sentiment'],
                type: $json['type'],
                autoReply: $json['auto_reply'] ?? null,
                usedAi: true,
                aiError: null,
            );
        } catch (ClientException $e) {
            $code = $e->getCode();

            if ($code === 401) {
                throw new AIUnauthorizedException(raw: ['message' => $e->getMessage()]);
            }

            throw new AIException(
                message: 'AI client error.',
                statusCode: $code,
                raw: ['message' => $e->getMessage()]
            );

        } catch (ServerException $e) {
            throw new AIServerException(raw: ['message' => $e->getMessage()]);
        } catch (ConnectException $e) {
            throw new AIConnectionException(raw: ['message' => $e->getMessage()]);
        } catch (Throwable $e) {
            throw new AIException(
                message: 'Unexpected AI error.',
                statusCode: 500,
                raw: ['message' => $e->getMessage()],
            );
        }
    }

    private function systemPrompt(): string
    {
        return <<<PROMPT
Ты — сервис анализа пользовательских обращений.
Верни строго JSON следующего формата:

{
  "sentiment": "positive | neutral | negative",
  "type": "question | feedback | complaint | general",
  "auto_reply": "string | null"
}

Никакого текста вне JSON.
PROMPT;
    }
}
