<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\ContactRequest;
use App\Services\Contact\Contracts\ContactHandlerInterface;
use App\Services\Contact\DTO\ContactDTO;
use App\Services\Contact\Exceptions\CommentAnalysisFailedException;
use App\Services\Contact\Exceptions\ContactNotificationFailedException;
use App\Services\Contact\Exceptions\RateLimitExceededException;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    public function __construct(
        private readonly ContactHandlerInterface $service,
    )
    {
    }

    public function store(ContactRequest $request): JsonResponse
    {
        $dto = ContactDTO::fromArray($request->validated());

        try {
            $result = $this->service->handleContact($dto);
        } catch (RateLimitExceededException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'retry_after' => $e->retryAfter,
            ], 429);
        } catch (CommentAnalysisFailedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'raw' => $e->raw,
            ], $e->statusCode);
        } catch (ContactNotificationFailedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'raw' => $e->raw,
            ], 502);
        }

        return response()->json([
            'message' => $result->message,
            'sentiment' => $result->sentiment,
            'type' => $result->type,
            'ai_used' => $result->aiUsed,
        ], 201);
    }
}
