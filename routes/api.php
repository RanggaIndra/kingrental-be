<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\PaymentController;

// Public Routes
Route::get('branches', [BranchController::class, 'index']);
Route::get('branches/{id}', [BranchController::class, 'show']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('vehicles', [VehicleController::class, 'index']);
Route::get('vehicles/{id}', [VehicleController::class, 'show']);
Route::post('/midtrans/webhook', [PaymentController::class, 'webhook']);

// Protected Routes (Harus Login)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Customer & Admin Routes
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::get('/bookings/{id}', [BookingController::class, 'show']);
    Route::get('/bookings/{id}/payment', [PaymentController::class, 'getPaymentToken']);

    // Admin Routes (Harus di DALAM auth:sanctum)
    Route::middleware(['role:super_admin,branch_admin'])->prefix('admin')->group(function () {
        Route::post('/vehicles', [VehicleController::class, 'store']);
        Route::post('/vehicles/{id}', [VehicleController::class, 'update']);
        Route::delete('/vehicles/{id}', [VehicleController::class, 'destroy']);
        Route::patch('/bookings/{id}/status', [BookingController::class, 'updateStatus']);
    });

    // Super Admin Only
    Route::middleware(['role:super_admin'])->prefix('admin')->group(function () {
        Route::post('/branches', [BranchController::class, 'store']);
        Route::put('/branches/{id}', [BranchController::class, 'update']);
        Route::delete('/branches/{id}', [BranchController::class, 'destroy']);
    });
});