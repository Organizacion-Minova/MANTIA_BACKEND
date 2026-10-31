<?php

/*
|--------------------------------------------------------------------------
| Rutas API MANTIA — versión integrada
|--------------------------------------------------------------------------
| Integra las rutas del repositorio y de la versión local:
| - Conserva CompanyController y VerifyTurnstile de la versión local.
| - Conserva autenticación, administración, equipos, herramientas, IA,
|   mediciones, exportaciones y demás rutas compartidas.
| - Mantiene permisos específicos en inspecciones y límites de solicitudes.
| - Permite confirmar/cancelar acciones de IA a Administrador y Jefe de mina.
|--------------------------------------------------------------------------
*/

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DevAuthController;
use App\Http\Controllers\MachineCategoryController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminApprovalController;
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
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\RoleManagementController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\InspectionQuestionController;
use App\Http\Controllers\InspectionFormatEquipmentController;
use App\Http\Controllers\InspectionEquipmentController;
use App\Http\Controllers\GasMeasurementExportController;
use App\Http\Middleware\VerifyTurnstile;
use App\Http\Controllers\TechnicalSheetExportController;
// Auth públicas
Route::post('/register', [AuthController::class, 'register'])->middleware(['throttle:10,10', VerifyTurnstile::class]);
Route::get('/email/verify/{id}', [AuthController::class, 'verifyEmail'])->middleware('signed')->name('verification.verify');
Route::post('/login', [AuthController::class, 'login'])->middleware(['throttle:10,1', VerifyTurnstile::class]);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware(['throttle:5,10', VerifyTurnstile::class]);
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:10,10');

Route::middleware('auth:sanctum')->post('/logout', function (Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return response()->json(['message' => 'Sesión cerrada']);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    $user = $request->user()->load('roles');
    // Lista de permisos para que el frontend sepa qué botones mostrar
    $user->setAttribute('permission_names', $user->getAllPermissions()->pluck('name')->values());
    return $user;
});

Route::middleware(['auth:sanctum', 'permission:daily-use.create|daily-use.update|machine.create|machine.update'])
    ->get('/roles-responsables', [RoleManagementController::class, 'responsables']);

Route::middleware(['auth:sanctum', 'role:Jefe de mina'])->group(function () {
    Route::get('/admin/requests', [AdminApprovalController::class, 'pendingRequests']);
    Route::post('/admin/approve/{id}', [AdminApprovalController::class, 'approve']);
    Route::post('/admin/reject/{id}', [AdminApprovalController::class, 'reject']);

    Route::get('/admin/users', [UserManagementController::class, 'index']);
    Route::put('/admin/users/{id}/toggle-active', [UserManagementController::class, 'toggleActive']);
    Route::put('/admin/users/{id}/password', [UserManagementController::class, 'resetPassword']);
    Route::put('/admin/users/{id}/role', [UserManagementController::class, 'changeRole']);

    Route::get('/admin/roles', [RoleManagementController::class, 'index']);
    Route::post('/admin/roles', [RoleManagementController::class, 'store']);
    Route::put('/admin/roles/{id}', [RoleManagementController::class, 'update']);
    Route::put('/admin/roles/{id}/toggle-active', [RoleManagementController::class, 'toggleActive']);
    Route::delete('/admin/roles/{id}', [RoleManagementController::class, 'destroy']);

    Route::get('/admin/backups/options', [BackupController::class, 'options']);
    Route::get('/admin/backups', [BackupController::class, 'index']);
    Route::post('/admin/backups', [BackupController::class, 'store']);
    Route::get('/admin/backups/{archivo}/download', [BackupController::class, 'download']);
    Route::get('/admin/permissions', [RoleManagementController::class, 'permissionsDisponibles']);
    Route::delete('/admin/backups/{archivo}', [BackupController::class, 'destroy']);
});

if (app()->environment('local')) {
    Route::post('/dev-login/{letra}', [DevAuthController::class, 'quickLogin']);
}

Route::middleware(['auth:sanctum', 'permission:machine-category.view'])->get('/machine-categories', [MachineCategoryController::class, 'index']);
Route::middleware(['auth:sanctum', 'permission:machine-category.create'])->post('/machine-categories', [MachineCategoryController::class, 'store']);
Route::middleware(['auth:sanctum', 'permission:machine-category.update'])->put('/machine-categories/{id}', [MachineCategoryController::class, 'update']);
Route::middleware(['auth:sanctum', 'permission:machine-category.delete'])->delete('/machine-categories/{id}', [MachineCategoryController::class, 'destroy']);


# Categorías de ubicaciones
Route::middleware(['auth:sanctum', 'permission:location-category.view'])->get('/location-category', [LocationCategoryController::class, 'index']);
Route::middleware(['auth:sanctum', 'permission:location-category.create'])->post('/location-category', [LocationCategoryController::class, 'store']);
Route::middleware(['auth:sanctum', 'permission:location-category.update'])->put('/location-category/{id}', [LocationCategoryController::class, 'update']);
Route::middleware(['auth:sanctum', 'permission:location-category.delete'])->delete('/location-category/{id}', [LocationCategoryController::class, 'destroy']);

# Ubicaciones
Route::middleware(['auth:sanctum', 'permission:location.view'])->get('/location', [LocationController::class, 'index']);
Route::middleware(['auth:sanctum', 'permission:location.create'])->post('/location', [LocationController::class, 'store']);
Route::middleware(['auth:sanctum', 'permission:location.update'])->put('/location/{id}', [LocationController::class, 'update']);
Route::middleware(['auth:sanctum', 'permission:location.delete'])->delete('/location/{id}', [LocationController::class, 'destroy']);

# Categorías de herramientas

Route::middleware(['auth:sanctum', 'permission:category-tools.view'])->get('/category-tools', [CategoriesToolsController::class, 'index']);

Route::middleware(['auth:sanctum', 'permission:category-tools.create'])->post('/category-tools', [CategoriesToolsController::class, 'store']);

Route::middleware(['auth:sanctum', 'permission:category-tools.delete'])->delete('/category-tools/{id}', [CategoriesToolsController::class, 'destroy']);

Route::middleware(['auth:sanctum', 'permission:category-tools.update'])->put('/category-tools/{id}', [CategoriesToolsController::class, 'update']);

Route::middleware(['auth:sanctum', 'permission:category-tools.view'])->get('/category-tools/{id}', [CategoriesToolsController::class, 'show']);

# Herramientas

Route::middleware(['auth:sanctum', 'permission:tool.view'])->get('/tools', [ToolsController::class, 'index']);

Route::middleware(['auth:sanctum', 'permission:tool.create'])->post('/tools', [ToolsController::class, 'store']);

Route::middleware(['auth:sanctum', 'permission:tool.update'])->put('/tools/{id}', [ToolsController::class, 'update']);

Route::middleware(['auth:sanctum', 'permission:tool.delete'])->delete('/tools/{id}', [ToolsController::class, 'destroy']);

// Notificaciones / alertas
# Equipos

Route::middleware(['auth:sanctum', 'permission:equipment.view'])->get('/equipment', [EquipmentController::class, 'index']);

Route::middleware(['auth:sanctum', 'permission:equipment.create'])->post('/equipment', [EquipmentController::class, 'store']);

Route::middleware(['auth:sanctum', 'permission:equipment.update'])->put('/equipment/{id}', [EquipmentController::class, 'update']);

Route::middleware(['auth:sanctum', 'permission:equipment.delete'])->delete('/equipment/{id}', [EquipmentController::class, 'destroy']);

Route::middleware(['auth:sanctum', 'permission:equipment.view'])->get('/equipment/{id}', [EquipmentController::class, 'show']);

# Maquinas

Route::middleware(['auth:sanctum', 'permission:machine.view'])->get('/machines',[MachinesController::class, 'index']);

Route::middleware(['auth:sanctum', 'permission:machine.create'])->post('/machines', [MachinesController::class, 'store']);

Route::middleware(['auth:sanctum', 'permission:machine.view'])->get('/machines/{id}', [MachinesController::class, 'show']);

Route::middleware(['auth:sanctum', 'permission:machine.update'])->put('/machines/{id}', [MachinesController::class, 'update']);

Route::middleware(['auth:sanctum', 'permission:machine.delete'])->delete('/machines/{id}', [MachinesController::class, 'destroy']);
# Medición de gases
Route::middleware(['auth:sanctum', 'permission:gas-measurements.view'])->get('/gas-measurements', [GasMeasurementController::class, 'index']);
Route::middleware(['auth:sanctum', 'permission:gas-measurements.create'])->post('/gas-measurements', [GasMeasurementController::class, 'store']);
Route::middleware(['auth:sanctum', 'permission:gas-measurements.view'])->get('/gas-measurements/{id}', [GasMeasurementController::class, 'show']);
Route::middleware(['auth:sanctum', 'permission:gas-measurements.update'])->put('/gas-measurements/detail/{detailId}', [GasMeasurementController::class, 'updateDetail']);
Route::middleware(['auth:sanctum', 'permission:gas-measurements.update'])->put('/gas-measurements/header/{id}', [GasMeasurementController::class, 'updateHeader']);
Route::middleware(['auth:sanctum', 'permission:gas-measurements.delete'])->delete('/gas-measurements/{id}', [GasMeasurementController::class, 'destroy']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notificaciones', [NotificacionController::class, 'index']);
    Route::post('/notificaciones/leer-todas', [NotificacionController::class, 'leerTodas']);
    Route::post('/notificaciones/{id}/leer', [NotificacionController::class, 'leer']);
});
// Asistente de IA (MANTIA IA): solo usuarios autenticados, máx. 20 mensajes por minuto
Route::middleware(['auth:sanctum', 'throttle:20,1'])->post('/ai/chat', [AiController::class, 'chat']);

// Botones "Confirmar" / "Cancelar" de las acciones que propone MANTIA IA: solo el Jefe de mina
Route::middleware(['auth:sanctum', 'role:Administrador|Jefe de mina', 'throttle:10,1'])->group(function () {
    Route::post('/ai/acciones/{id}/confirmar', [AiActionController::class, 'confirmar']);
    Route::post('/ai/acciones/{id}/cancelar', [AiActionController::class, 'cancelar']);
});

Route::middleware('auth:sanctum')->prefix('dashboard')->group(function () {
    Route::get('/', [DashboardController::class, 'index']);
    Route::get('/{section}', [DashboardController::class, 'show'])->where('section', '[a-z-]+');
});


# Uso diario

Route::middleware(['auth:sanctum', 'permission:daily-use.view'])->get('/dailyuse', [DailyUseController::class, 'index']);

Route::middleware(['auth:sanctum', 'permission:daily-use.create'])->post('/dailyuse', [DailyUseController::class, 'store']);

Route::middleware(['auth:sanctum', 'permission:daily-use.delete'])->delete('/dailyuse/{id}', [DailyUseController::class, 'destroy']);

Route::middleware(['auth:sanctum', 'permission:daily-use.update'])->put('/dailyuse/{id}', [DailyUseController::class, 'update']);

# Preguntas inspeccion

Route::middleware(['auth:sanctum', 'permission:inspection.view'])->get('/inspection-questions', [InspectionQuestionController::class, 'index']);

Route::middleware(['auth:sanctum', 'permission:inspection.create'])->post('/inspection-questions', [InspectionQuestionController::class, 'store']);

# Formato inspeccion equipos

Route::middleware(['auth:sanctum'])->get('/inspection-formats-equipment', [InspectionFormatEquipmentController::class, 'index']);

Route::middleware(['auth:sanctum'])->get('/inspection-formats-equipment/{id}', [InspectionFormatEquipmentController::class, 'show']);

Route::middleware(['auth:sanctum'])->post('/inspection-formats-equipment', [InspectionFormatEquipmentController::class, 'store']);

Route::middleware(['auth:sanctum'])->get('/equipment/{id}/inspection-format', [InspectionFormatEquipmentController::class, 'byEquipment']);

Route::middleware(['auth:sanctum'])->get('/equipment/{id}/inspections', [InspectionEquipmentController::class, 'indexByEquipment']);

Route::middleware(['auth:sanctum'])->get('/inspection-formats/{id}/week', [InspectionEquipmentController::class, 'week']);

Route::middleware(['auth:sanctum'])->get('/inspections/{id}', [InspectionEquipmentController::class, 'show']);

Route::middleware(['auth:sanctum'])->post('/inspections', [InspectionEquipmentController::class, 'store']);

# Exportaciones unitarias Medición de gases
Route::middleware(['auth:sanctum', 'permission:gas-measurements.view'])->get('/gas-measurements/{id}/export/excel', [GasMeasurementExportController::class, 'exportarUnitarioExcel']);
Route::middleware(['auth:sanctum', 'permission:gas-measurements.view'])->get('/gas-measurements/{id}/export/pdf', [GasMeasurementExportController::class, 'exportarUnitarioPdf']);

# Exportaciones por rango de fechas Medición de gases
Route::middleware(['auth:sanctum', 'permission:gas-measurements.view'])->get('/gas-measurements/export/range/excel', [GasMeasurementExportController::class, 'exportarRangoExcel']);
Route::middleware(['auth:sanctum', 'permission:gas-measurements.view'])->get('/gas-measurements/export/range/pdf', [GasMeasurementExportController::class, 'exportarRangoPdf']);

# Exportaciones unitarias para Ficha técnica Equipos
Route::middleware('auth:sanctum', 'permission:equipment.view')->get('/equipment/{id}/export/excel', [TechnicalSheetExportController::class, 'equipmentExcel']);
Route::middleware('auth:sanctum', 'permission:equipment.view')->get('/equipment/{id}/export/pdf', [TechnicalSheetExportController::class, 'equipmentPdf']);