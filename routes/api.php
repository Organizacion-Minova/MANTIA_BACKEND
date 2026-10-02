<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DevAuthController;
use App\Http\Controllers\MachineController;
use App\Models\Location;
use App\Models\MachineCategory;
use App\Http\Controllers\LocationController;

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

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notificaciones', fn (Request $request) => $request->user()->notifications);
    Route::get('/notificaciones/no-leidas', fn (Request $request) => $request->user()->unreadNotifications);
    Route::post('/notificaciones/{id}/leer', function (Request $request, $id) {
        $request->user()->notifications()->findOrFail($id)->markAsRead();
        return response()->json(['ok' => true]);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/locations', fn () => Location::where('status', 'active')->orderBy('name')->get());
    Route::get('/machine-categories', fn () => MachineCategory::where('status', 'active')->orderBy('name')->get());
    Route::get('/machines', [MachineController::class, 'index']);
    Route::post('/machines', [MachineController::class, 'store']);
});

// Route::get('/login', function () {
//     return response()->json(['message' => 'No autenticado.'], 401);
// })->name('login');


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/location-categories', fn () => \App\Models\LocationCategory::where('status', 'active')->orderBy('name')->get());
    Route::post('/locations', [LocationController::class, 'store']);
});