<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DevAuthController;
use App\Http\Controllers\MachineCategoryController;

Route::post('/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (! Auth::attempt($request->only('email', 'password'))) {
        return response()->json(['message' => 'Credenciales inválidas'], 401);
    }

    $request->session()->regenerate();

    return response()->json(['user' => Auth::user()]);
});

Route::post('/logout', function (Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return response()->json(['message' => 'Sesión cerrada']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

if (app()->environment('local')) {
    Route::post('/dev-login/{letra}', [DevAuthController::class, 'quickLogin']);
}

Route::middleware('auth:sanctum')->get('/machine-categories', [MachineCategoryController::class, 'index']);
Route::middleware('auth:sanctum')->post('/machine-categories', [MachineCategoryController::class, 'store']);
Route::middleware('auth:sanctum')->put('/machine-categories/{id}', [MachineCategoryController::class, 'update']);
Route::middleware('auth:sanctum')->delete('/machine-categories/{id}', [MachineCategoryController::class, 'destroy']);
