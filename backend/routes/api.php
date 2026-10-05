<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ComplaintController;
use App\Http\Controllers\Api\ResponseController;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/status', function () {
    return response()->json([
        'success' => true,
        'message' => 'API Sistem Pengaduan Mahasiswa berjalan.',
    ]);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


/*
|--------------------------------------------------------------------------
| Protected Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // Authentication
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Categories
    Route::get('/categories', [CategoryController::class, 'index']);

    // Complaints
    Route::apiResource('complaints', ComplaintController::class);

    // Responses
    Route::get(
        '/complaints/{complaintId}/responses',
        [ResponseController::class, 'index']
    );

    Route::post(
        '/complaints/{complaintId}/responses',
        [ResponseController::class, 'store']
    );

    Route::delete(
        '/complaints/{complaintId}/responses/{responseId}',
        [ResponseController::class, 'destroy']
    );
});