<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Services\Contact\ContactService;
use App\Services\Contact\DTO\ContactDTO;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    public function __construct(
        private readonly ContactService $service,
    ) {}

    public function store(ContactRequest $request): JsonResponse
    {
        $dto = ContactDTO::fromArray($request->validated());

        $result = $this->service->handleContact($dto);

        return response()->json($result->toArray(), 201);
    }
}
