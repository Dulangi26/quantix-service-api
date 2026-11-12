<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RequestWorkflowController;
use App\Http\Controllers\Api\ServiceRequestController;
use Illuminate\Support\Facades\Route;

// Public authentication routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/requests', [ServiceRequestController::class, 'index']);

    Route::post('/requests', [ServiceRequestController::class, 'store']);

    Route::get('/requests/{serviceRequest}', [ServiceRequestController::class, 'show'])
        ->whereNumber('serviceRequest');

    Route::put('/requests/{serviceRequest}', [ServiceRequestController::class, 'update'])
        ->whereNumber('serviceRequest');

    Route::post('/requests/{serviceRequest}/submit', [
        RequestWorkflowController::class,
        'submit',
    ])->whereNumber('serviceRequest');

    Route::post('/requests/{serviceRequest}/decision', [
        RequestWorkflowController::class,
        'decision',
    ])->whereNumber('serviceRequest');

    Route::post('/requests/{serviceRequest}/assign', [
        RequestWorkflowController::class,
        'assign',
    ])->whereNumber('serviceRequest');

    Route::post('/requests/{serviceRequest}/status', [
        RequestWorkflowController::class,
        'updateStatus',
    ])->whereNumber('serviceRequest');

    Route::post('/requests/{serviceRequest}/comments', [
        RequestWorkflowController::class,
        'addComment',
    ])->whereNumber('serviceRequest');

});
