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
use App\Http\Controllers\CategoriesToolsController;
use App\Http\Controllers\ToolsController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\AiActionController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\GasMeasurementController;
use App\Http\Controllers\MachinesController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DailyUseController;
// Auth públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->post('/logout', function (Request $request) {
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

# Empresas
Route::middleware('auth:sanctum')->get('/company', [CompanyController::class, 'index']);
Route::middleware('auth:sanctum')->post('/company', [CompanyController::class, 'store']);
Route::middleware('auth:sanctum')->put('/company/{id}', [CompanyController::class, 'update']);
Route::middleware('auth:sanctum')->delete('/company/{id}', [CompanyController::class, 'destroy']);

# Categorías de ubicaciones
Route::middleware('auth:sanctum')->get('/location-category', [LocationCategoryController::class, 'index']);
Route::middleware('auth:sanctum')->post('/location-category', [LocationCategoryController::class, 'store']);
Route::middleware('auth:sanctum')->put('/location-category/{id}', [LocationCategoryController::class, 'update']);
Route::middleware('auth:sanctum')->delete('/location-category/{id}', [LocationCategoryController::class, 'destroy']);

# Ubicaciones
Route::middleware('auth:sanctum')->get('/location', [LocationController::class, 'index']);
Route::middleware('auth:sanctum')->post('/location', [LocationController::class, 'store']);
Route::middleware('auth:sanctum')->put('/location/{id}', [LocationController::class, 'update']);
Route::middleware('auth:sanctum')->delete('/location/{id}', [LocationController::class, 'destroy']);

# Categorías de herramientas

Route::middleware('auth:sanctum')->get('/category-tools', [CategoriesToolsController::class, 'index']);

Route::middleware("auth:sanctum")->post('/category-tools', [CategoriesToolsController::class, 'store']);

Route::middleware('auth:sanctum')->delete('/category-tools/{id}', [CategoriesToolsController::class, 'destroy']);

Route::middleware('auth:sanctum')->put('/category-tools/{id}', [CategoriesToolsController::class, 'update']);

Route::middleware('auth:sanctum')->get('/category-tools/{id}', [CategoriesToolsController::class, 'show']);

# Herramientas

Route::middleware('auth:sanctum')->get('/tools', [ToolsController::class, 'index']);

Route::middleware('auth:sanctum')->post('/tools', [ToolsController::class, 'store']);

Route::middleware('auth:sanctum')->put('/tools/{id}', [ToolsController::class, 'update']);

Route::middleware('auth:sanctum')->delete('/tools/{id}', [ToolsController::class, 'destroy']);

// Notificaciones / alertas
# Equipos

Route::middleware('auth:sanctum')->get('/equipment', [EquipmentController::class, 'index']);

Route::middleware('auth:sanctum')->post('/equipment', [EquipmentController::class, 'store']);

Route::middleware('auth:sanctum')->put('/equipment/{id}', [EquipmentController::class, 'update']);

Route::middleware('auth:sanctum')->delete('/equipment/{id}', [EquipmentController::class, 'destroy']);

Route::middleware('auth:sanctum')->get('/equipment/{id}', [EquipmentController::class, 'show']);

# Maquinas

Route::middleware('auth:sanctum')->get('/machines',[MachinesController::class, 'index']);

Route::middleware('auth:sanctum')->post('/machines', [MachinesController::class, 'store']);

Route::middleware('auth:sanctum')->get('/machines/{id}', [MachinesController::class, 'show']);

Route::middleware('auth:sanctum')->put('/machines/{id}', [MachinesController::class, 'update']);

Route::middleware('auth:sanctum')->delete('/machines/{id}', [MachinesController::class, 'destroy']);
# Medición de gases
Route::middleware('auth:sanctum')->get('/gas-measurements', [GasMeasurementController::class, 'index']);
Route::middleware('auth:sanctum')->post('/gas-measurements', [GasMeasurementController::class, 'store']);
Route::middleware('auth:sanctum')->get('/gas-measurements/{id}', [GasMeasurementController::class, 'show']);
Route::middleware('auth:sanctum')->put('/gas-measurements/detail/{detailId}', [GasMeasurementController::class, 'updateDetail']);
Route::middleware('auth:sanctum')->put('/gas-measurements/header/{id}', [GasMeasurementController::class, 'updateHeader']);
Route::middleware('auth:sanctum')->delete('/gas-measurements/{id}', [GasMeasurementController::class, 'destroy']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notificaciones', [NotificacionController::class, 'index']);
    Route::post('/notificaciones/leer-todas', [NotificacionController::class, 'leerTodas']);
    Route::post('/notificaciones/{id}/leer', [NotificacionController::class, 'leer']);
});
// Asistente de IA (MANTIA IA): solo usuarios autenticados, máx. 20 mensajes por minuto
Route::middleware(['auth:sanctum', 'throttle:20,1'])->post('/ai/chat', [AiController::class, 'chat']);

// Botones "Confirmar" / "Cancelar" de las acciones que propone MANTIA IA: solo administradores
Route::middleware(['auth:sanctum', 'role:Administrador', 'throttle:10,1'])->group(function () {
    Route::post('/ai/acciones/{id}/confirmar', [AiActionController::class, 'confirmar']);
    Route::post('/ai/acciones/{id}/cancelar', [AiActionController::class, 'cancelar']);
});

Route::middleware('auth:sanctum')->prefix('dashboard')->group(function () {
    Route::get('/', [DashboardController::class, 'index']);
    Route::get('/{section}', [DashboardController::class, 'show'])->where('section', '[a-z-]+');
});


# Uso diario

Route::middleware('auth:sanctum')->get('/dailyuse', [DailyUseController::class, 'index']);

Route::middleware('auth:sanctum')->post('/dailyuse', [DailyUseController::class, 'store']);

Route::middleware('auth:sanctum')->delete('/dailyuse/{id}', [DailyUseController::class, 'destroy']);

Route::middleware('auth:sanctum')->put('/dailyuse/{id}', [DailyUseController::class, 'update']);