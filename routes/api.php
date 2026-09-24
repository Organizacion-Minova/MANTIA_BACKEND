<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DevAuthController;
use App\Http\Controllers\MachineCategoryController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminApprovalController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\LocationCategoryController;
use App\Http\Controllers\LocationController;

// Auth públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::post('/logout', function (Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return response()->json(['message' => 'Sesión cerrada']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user()->load('roles');
});

// Admin Aprobaciones
Route::middleware(['auth:sanctum', 'role:Administrador'])->group(function () {
    Route::get('/admin/requests', [AdminApprovalController::class, 'pendingRequests']);
    Route::post('/admin/approve/{id}', [AdminApprovalController::class, 'approve']);
    Route::post('/admin/reject/{id}', [AdminApprovalController::class, 'reject']);
});

if (app()->environment('local')) {
    Route::post('/dev-login/{letra}', [DevAuthController::class, 'quickLogin']);
}

Route::middleware('auth:sanctum')->get('/machine-categories', [MachineCategoryController::class, 'index']);
Route::middleware('auth:sanctum')->post('/machine-categories', [MachineCategoryController::class, 'store']);
Route::middleware('auth:sanctum')->put('/machine-categories/{id}', [MachineCategoryController::class, 'update']);
Route::middleware('auth:sanctum')->delete('/machine-categories/{id}', [MachineCategoryController::class, 'destroy']);

Route::middleware('auth:sanctum')->get('/company', [CompanyController::class, 'index']);
Route::middleware('auth:sanctum')->post('/company', [CompanyController::class, 'store']);
Route::middleware('auth:sanctum')->put('/company/{id}', [CompanyController::class, 'update']);
Route::middleware('auth:sanctum')->delete('/company/{id}', [CompanyController::class, 'destroy']);

Route::middleware('auth:sanctum')->get('/location-category', [LocationCategoryController::class, 'index']);
Route::middleware('auth:sanctum')->post('/location-category', [LocationCategoryController::class, 'store']);
Route::middleware('auth:sanctum')->put('/location-category/{id}', [LocationCategoryController::class, 'update']);
Route::middleware('auth:sanctum')->delete('/location-category/{id}', [LocationCategoryController::class, 'destroy']);

Route::middleware('auth:sanctum')->get('/location', [LocationController::class, 'index']);
Route::middleware('auth:sanctum')->post('/location', [LocationController::class, 'store']);
Route::middleware('auth:sanctum')->put('/location/{id}', [LocationController::class, 'update']);
Route::middleware('auth:sanctum')->delete('/location/{id}', [LocationController::class, 'destroy']);