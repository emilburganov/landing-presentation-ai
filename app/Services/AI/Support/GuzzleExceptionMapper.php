<?php

namespace App\Services\AI\Support;

use App\Services\AI\Exceptions\AIConnectionException;
use App\Services\AI\Exceptions\AIException;
use App\Services\AI\Exceptions\AIServerException;
use App\Services\AI\Exceptions\AIUnauthorizedException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use Psr\Log\LoggerInterface;
use Throwable;

readonly class GuzzleExceptionMapper
{
    public function __construct(
        private LoggerInterface $logger,
    )
    {
    }

    public function map(Throwable $e): AIException
    {
        if ($e instanceof ClientException) {
            $code = $e->getCode();

            $this->logger->warning('ai.http.client_error', [
                'status_code' => $code,
                'message' => $e->getMessage(),
            ]);

            if ($code === 401) {
                return new AIUnauthorizedException(raw: ['message' => $e->getMessage()]);
            }

            return new AIException(
                message: 'AI client error.',
                statusCode: $code,
                raw: ['message' => $e->getMessage()],
            );
        }

        if ($e instanceof ServerException) {
            $this->logger->error('ai.http.server_error', [
                'message' => $e->getMessage(),
            ]);

            return new AIServerException(raw: ['message' => $e->getMessage()]);
        }

        if ($e instanceof ConnectException) {
            $this->logger->error('ai.http.connection_error', [
                'message' => $e->getMessage(),
            ]);

            return new AIConnectionException(raw: ['message' => $e->getMessage()]);
        }

        $this->logger->error('ai.http.unexpected_error', [
            'exception' => $e::class,
            'message' => $e->getMessage(),
        ]);

        return new AIException(
            message: 'Unexpected AI error.',
            statusCode: 500,
            raw: ['message' => $e->getMessage()],
        );
    }
}
