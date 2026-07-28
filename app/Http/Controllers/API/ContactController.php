<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\ContactRequest;
use App\Services\Contact\Contracts\ContactHandlerInterface;
use App\Services\Contact\DTO\ContactDTO;
use App\Services\Contact\Exceptions\CommentAnalysisFailedException;
use App\Services\Contact\Exceptions\RateLimitExceededException;
use Illuminate\Http\JsonResponse;
use Psr\Log\LoggerInterface;

class ContactController extends Controller
{
    public function __construct(
        private readonly ContactHandlerInterface $service,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function store(ContactRequest $request): JsonResponse
    {
        $dto = ContactDTO::fromArray($request->validated());

        $this->logger->info('contact.request', [
            'email' => $dto->email,
            'comment_length' => mb_strlen($dto->comment),
        ]);

        try {
            $result = $this->service->handleContact($dto);
        } catch (RateLimitExceededException $e) {
            $this->logger->warning('contact.rate_limited', [
                'email' => $dto->email,
                'retry_after' => $e->retryAfter,
            ]);

            return response()->json([
                'message' => $e->getMessage(),
                'retry_after' => $e->retryAfter,
            ], 429);
        } catch (CommentAnalysisFailedException $e) {
            $this->logger->error('contact.analysis_failed', [
                'email' => $dto->email,
                'message' => $e->getMessage(),
                'status_code' => $e->statusCode,
                'raw' => $e->raw,
            ]);

            return response()->json([
                'message' => $e->getMessage(),
                'raw' => $e->raw,
            ], $e->statusCode);
        }

        $this->logger->info('contact.accepted', [
            'email' => $dto->email,
            'sentiment' => $result->sentiment,
            'type' => $result->type,
            'ai_used' => $result->aiUsed,
        ]);

        return response()->json([
            'message' => $result->message,
            'sentiment' => $result->sentiment,
            'type' => $result->type,
            'ai_used' => $result->aiUsed,
        ], 201);
    }
}
