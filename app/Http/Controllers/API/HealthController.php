<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Health\HealthChecker;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __construct(
        private readonly HealthChecker $health,
    ) {
    }

    public function index(): JsonResponse
    {
        $result = $this->health->check();

        $statusCode = match ($result['status']) {
            'down' => 503,
            default => 200,
        };

        return response()->json([
            'status' => $result['status'],
            'service' => config('app.name'),
            'timestamp' => now()->toIso8601String(),
            'checks' => $result['checks'],
        ], $statusCode);
    }
}
