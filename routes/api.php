<?php

use App\Http\Controllers\API\ContactController;
use App\Http\Controllers\API\MetricsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/contact', [ContactController::class, 'store']);
Route::get('/metrics', [MetricsController::class, 'index']);
