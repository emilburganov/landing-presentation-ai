<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Contact\ContactMetricsService;
use Illuminate\Http\JsonResponse;

class MetricsController extends Controller
{
    public function __construct(
        private readonly ContactMetricsService $metrics,
    )
    {
    }

    public function index(): JsonResponse
    {
        return response()->json(
            $this->metrics->getMetrics()->toArray(),
        );
    }
}
