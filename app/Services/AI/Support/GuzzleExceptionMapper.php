<?php

namespace App\Services\AI\Support;

use App\Services\AI\Exceptions\AIConnectionException;
use App\Services\AI\Exceptions\AIException;
use App\Services\AI\Exceptions\AIServerException;
use App\Services\AI\Exceptions\AIUnauthorizedException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ServerException;
use Throwable;

readonly class GuzzleExceptionMapper
{
    public function map(Throwable $e): AIException
    {
        if ($e instanceof ClientException) {
            $code = $e->getCode();

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
            return new AIServerException(raw: ['message' => $e->getMessage()]);
        }

        if ($e instanceof ConnectException) {
            return new AIConnectionException(raw: ['message' => $e->getMessage()]);
        }

        return new AIException(
            message: 'Unexpected AI error.',
            statusCode: 500,
            raw: ['message' => $e->getMessage()],
        );
    }
}
