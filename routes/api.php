<?php

/*
|--------------------------------------------------------------------------
| Rutas API MANTIA — versión integrada
|--------------------------------------------------------------------------
| Integra las rutas del repositorio y de la versión local:
| - Conserva CompanyController. El captcha es LOCAL (VerifyCaptcha), funciona sin internet.
| - Conserva autenticación, administración, equipos, herramientas, IA,
|   mediciones, exportaciones y demás rutas compartidas.
| - Mantiene permisos específicos en inspecciones y límites de solicitudes.
| - Permite confirmar/cancelar acciones de IA a Jefe de mina y Superadministrador.
| - Superadministrador: acceso completo, historial con reversión y pruebas automatizadas.
|--------------------------------------------------------------------------
*/

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
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
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\InspectionFormatEquipmentController;
use App\Http\Controllers\InspectionEquipmentController;
use App\Http\Controllers\InspectionFormatMachineController;
use App\Http\Controllers\MachineRecordController;
use App\Http\Controllers\GasMeasurementExportController;
use App\Http\Middleware\VerifyCaptcha;
use App\Services\CaptchaLocal;
use App\Http\Controllers\TechnicalSheetExportController;
use App\Http\Controllers\DailyUseExportController;
use App\Http\Controllers\InspectionEquipmentExportController;
use App\Http\Controllers\ToolsMachineController;
use App\Http\Controllers\InspectionOverviewController;
use App\Http\Controllers\InspectionMachineExportController;
use App\Http\Controllers\PreoperationalMachineExportController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\TestRunnerController;
use App\Http\Controllers\AlertaExportController;
use App\Http\Controllers\LocationInventoryController;
use App\Http\Controllers\LocationExportController;
// Auth públicas
// Imagen del captcha local (sin internet). Cada imagen sirve una sola vez y vence en 5 minutos.
Route::get('/captcha', fn () => response()->json(CaptchaLocal::crear())
    ->header('Cache-Control', 'no-store'))->middleware('throttle:30,1,captcha');

Route::post('/register', [AuthController::class, 'register'])->middleware(['throttle:10,10,register', VerifyCaptcha::class]);
Route::get('/email/verify/{id}', [AuthController::class, 'verifyEmail'])->middleware('signed')->name('verification.verify');
Route::post('/login', [AuthController::class, 'login'])->middleware(['throttle:10,1,login', VerifyCaptcha::class]);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware(['throttle:5,10,forgot-password', VerifyCaptcha::class]);
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:10,10,reset-password');

Route::middleware('auth:sanctum')->post('/logout', function (Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return response()->json(['message' => 'Sesión cerrada']);
});

Route::middleware('auth:sanctum')->get('/user', fn (Request $request) => \App\Support\Roles::usuarioConPermisos($request->user()));

// Perfil propio: datos, foto y contraseña
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'password'])->middleware('throttle:5,1,profile-password');
});

Route::middleware(['auth:sanctum', 'permission:daily-use.create|daily-use.update|machine.create|machine.update'])
    ->get('/roles-responsables', [RoleManagementController::class, 'responsables']);

// Sesión actual SIN exigir login: responde 200 con user = null si no hay sesión.
// La usa el frontend al cargar, así las páginas públicas no muestran un 401 en la consola.
Route::get('/session', function (Request $request) {
    $user = Auth::guard('web')->user();

    // Cuenta desactivada o sin roles activos: se cierra la sesión y se responde como "sin sesión"
    $bloqueada = $user && (
        in_array($user->status, ['inactive', 'rejected', 'pending'], true)
        || $user->is_active === false
        || ($user->roles()->exists() && ! $user->roles()->where('roles.is_active', true)->exists())
    );

    if ($bloqueada) {
        Auth::guard('web')->logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
        $user = null;
    }

    return response()->json(['user' => $user ? \App\Support\Roles::usuarioConPermisos($user) : null]);
})->middleware('throttle:120,1,session');

// Panel de administración: Jefe de mina y Superadministrador
Route::middleware(['auth:sanctum', 'role:Jefe de mina|Superadministrador'])->group(function () {
    Route::get('/admin/requests', [AdminApprovalController::class, 'pendingRequests']);
    Route::post('/admin/approve/{id}', [AdminApprovalController::class, 'approve'])->whereNumber('id');
    Route::post('/admin/reject/{id}', [AdminApprovalController::class, 'reject'])->whereNumber('id');

    Route::get('/admin/users', [UserManagementController::class, 'index']);
    Route::post('/admin/users', [UserManagementController::class, 'store'])->middleware('throttle:20,1,admin-users');
    Route::put('/admin/users/{id}/toggle-active', [UserManagementController::class, 'toggleActive'])->whereNumber('id');
    Route::put('/admin/users/{id}/password', [UserManagementController::class, 'resetPassword'])->whereNumber('id')->middleware('throttle:10,1,admin-users-id-password');
    Route::put('/admin/users/{id}/role', [UserManagementController::class, 'changeRole'])->whereNumber('id');

    Route::get('/admin/roles', [RoleManagementController::class, 'index']);
    Route::post('/admin/roles', [RoleManagementController::class, 'store']);
    Route::put('/admin/roles/{id}', [RoleManagementController::class, 'update'])->whereNumber('id');
    Route::put('/admin/roles/{id}/toggle-active', [RoleManagementController::class, 'toggleActive'])->whereNumber('id');
    Route::delete('/admin/roles/{id}', [RoleManagementController::class, 'destroy'])->whereNumber('id');
    Route::get('/admin/permissions', [RoleManagementController::class, 'permissionsDisponibles']);

    // Copias de seguridad generales: la primera copia confirma la contraseña; las siguientes acciones usan BACKUP_ADMIN_KEY.
    Route::get('/admin/backups', [BackupController::class, 'index']);
    Route::post('/admin/backups', [BackupController::class, 'store'])->middleware('throttle:6,1,admin-backups');
    Route::post('/admin/backups/{archivo}/download', [BackupController::class, 'download'])->middleware('throttle:6,1,admin-backups-archivo-download');
    Route::post('/admin/backups/{archivo}/restore', [BackupController::class, 'restore'])->middleware('throttle:6,1,admin-backups-archivo-restore');
    Route::delete('/admin/backups/{archivo}', [BackupController::class, 'destroy'])->middleware('throttle:6,1,admin-backups-archivo');

    // Historial de cambios (el Jefe de mina no ve lo del Superadministrador ni de los Visitantes)
    Route::get('/admin/activity', [ActivityLogController::class, 'index']);
    Route::get('/admin/activity/{id}', [ActivityLogController::class, 'show'])->whereNumber('id');
});

// Solo el Superadministrador
Route::middleware(['auth:sanctum', 'role:Superadministrador'])->group(function () {
    Route::post('/admin/activity/{id}/revert', [ActivityLogController::class, 'revert'])->whereNumber('id')->middleware('throttle:10,1,admin-activity-id-revert');
    Route::get('/admin/tests', [TestRunnerController::class, 'show']);
    Route::post('/admin/tests/run', [TestRunnerController::class, 'run'])->middleware('throttle:3,1,admin-tests-run');
});

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
Route::middleware(['auth:sanctum', 'permission:location.view'])->get('/location-options', [LocationController::class, 'options']);
Route::middleware(['auth:sanctum', 'permission:location.view'])->get('/location/{id}/inventory', [LocationInventoryController::class, 'show']);
Route::middleware(['auth:sanctum', 'permission:location.view'])->get('/location/{id}/export/excel', [LocationExportController::class, 'inventarioExcel']);
Route::middleware(['auth:sanctum', 'permission:location.view'])->get('/location/{id}/export/pdf', [LocationExportController::class, 'inventarioPdf']);

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

Route::middleware(['auth:sanctum', 'permission:equipment.view'])->get('/equipment-options', [EquipmentController::class, 'options']);


# Maquinas

Route::middleware(['auth:sanctum', 'permission:machine.view'])->get('/machines',[MachinesController::class, 'index']);

Route::middleware(['auth:sanctum', 'permission:machine.create'])->post('/machines', [MachinesController::class, 'store']);

Route::middleware(['auth:sanctum', 'permission:machine.view'])->get('/machines/{id}', [MachinesController::class, 'show']);

Route::middleware(['auth:sanctum', 'permission:machine.update'])->put('/machines/{id}', [MachinesController::class, 'update']);

Route::middleware(['auth:sanctum', 'permission:machine.delete'])->delete('/machines/{id}', [MachinesController::class, 'destroy']);

Route::middleware(['auth:sanctum', 'permission:machine.view'])->get('/machine-options', [MachinesController::class, 'options']);

# Consumibles maquinas

Route::middleware(['auth:sanctum', 'permission:machine.view'])->get('consumable-tools', [ToolsMachineController::class, 'catalog']);

Route::middleware(['auth:sanctum', 'permission:machine.view'])->get('machine-tools', [ToolsMachineController::class, 'index']);
Route::middleware(['auth:sanctum', 'permission:machine.update'])->post('machine-tools', [ToolsMachineController::class, 'store']);
Route::middleware(['auth:sanctum', 'permission:machine.update'])->put('machine-tools/{machine_tool}', [ToolsMachineController::class, 'update']);
Route::middleware(['auth:sanctum', 'permission:machine.update'])->delete('machine-tools/{machine_tool}', [ToolsMachineController::class, 'destroy']);

# Medición de gases
Route::middleware(['auth:sanctum', 'permission:gas-measurements.view'])->get('/gas-measurements', [GasMeasurementController::class, 'index']);
Route::middleware(['auth:sanctum', 'permission:gas-measurements.create'])->post('/gas-measurements', [GasMeasurementController::class, 'store']);
Route::middleware(['auth:sanctum', 'permission:gas-measurements.view'])->get('/gas-measurements/{id}', [GasMeasurementController::class, 'show']);
Route::middleware(['auth:sanctum', 'permission:gas-measurements.update'])->put('/gas-measurements/detail/{detailId}', [GasMeasurementController::class, 'updateDetail']);
Route::middleware(['auth:sanctum', 'permission:gas-measurements.update'])->put('/gas-measurements/header/{id}', [GasMeasurementController::class, 'updateHeader']);
Route::middleware(['auth:sanctum', 'permission:gas-measurements.delete'])->delete('/gas-measurements/{id}', [GasMeasurementController::class, 'destroy']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notificaciones', [NotificacionController::class, 'index']);
    Route::get('/notificaciones/historial', [NotificacionController::class, 'historial']);
    Route::get('/notificaciones/export/excel', [AlertaExportController::class, 'alertasExcel']);
    Route::get('/notificaciones/export/pdf', [AlertaExportController::class, 'alertasPdf']);
    Route::post('/notificaciones/leer-todas', [NotificacionController::class, 'leerTodas']);
    Route::post('/notificaciones/{id}/leer', [NotificacionController::class, 'leer']);
});
// Asistente de IA (MANTIA IA): solo usuarios autenticados, máx. 20 mensajes por minuto
Route::middleware(['auth:sanctum', 'throttle:20,1,ai-chat'])->post('/ai/chat', [AiController::class, 'chat']);

// Botones "Confirmar" / "Cancelar" de las acciones que propone MANTIA IA: Jefe de mina y Superadministrador
Route::middleware(['auth:sanctum', 'role:Jefe de mina|Superadministrador', 'throttle:10,1,ai-acciones'])->group(function () {
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


# Formato inspeccion equipos

Route::middleware(['auth:sanctum', 'permission:inspection.view'])->get('/inspection-formats-equipment', [InspectionFormatEquipmentController::class, 'index']);

Route::middleware(['auth:sanctum', 'permission:inspection.view'])->get('/inspection-formats-equipment/{id}', [InspectionFormatEquipmentController::class, 'show']);

Route::middleware(['auth:sanctum', 'permission:inspection.update'])->post('/inspection-formats-equipment', [InspectionFormatEquipmentController::class, 'store']);

Route::middleware(['auth:sanctum', 'permission:inspection.update'])->put('/inspection-formats-equipment/{id}', [InspectionFormatEquipmentController::class, 'update']);

Route::middleware(['auth:sanctum', 'permission:inspection.view'])->get('/equipment/{id}/inspection-format', [InspectionFormatEquipmentController::class, 'byEquipment']);

Route::middleware(['auth:sanctum', 'permission:inspection.view'])->get('/equipment/{id}/has-inspection-format', [InspectionFormatEquipmentController::class, 'hasFormat']);

Route::middleware(['auth:sanctum', 'permission:inspection.view'])->get('/equipment/{id}/inspections', [InspectionEquipmentController::class, 'indexByEquipment']);

Route::middleware(['auth:sanctum', 'permission:inspection.view'])->get('/inspection-formats/{id}/week', [InspectionEquipmentController::class, 'week']);

Route::middleware(['auth:sanctum', 'permission:inspection.view'])->get('/inspections/{id}', [InspectionEquipmentController::class, 'show']);

Route::middleware(['auth:sanctum', 'permission:inspection.create'])->post('/inspections', [InspectionEquipmentController::class, 'store']);

# Formato inspeccion y preoperacional maquinas

Route::middleware(['auth:sanctum', 'permission:inspection.view|daily-use.view'])->get('/machines/{id}/inspection-format', [InspectionFormatMachineController::class, 'byMachine']);

Route::middleware(['auth:sanctum', 'permission:inspection.update'])->post('/inspection-formats-machine', [InspectionFormatMachineController::class, 'store']);

Route::middleware(['auth:sanctum', 'permission:inspection.update'])->put('/inspection-formats-machine/{id}', [InspectionFormatMachineController::class, 'update']);

Route::middleware(['auth:sanctum', 'permission:inspection.view'])->get('/machines/{id}/records/{kind}', [MachineRecordController::class, 'index']);

Route::middleware(['auth:sanctum', 'permission:inspection.view'])->get('/machines/{id}/records/{kind}/period', [MachineRecordController::class, 'period']);

Route::middleware(['auth:sanctum', 'permission:inspection.view'])->get('/machine-records/{kind}/{id}', [MachineRecordController::class, 'show']);

Route::middleware(['auth:sanctum', 'permission:inspection.create'])->post('/machine-records/{kind}', [MachineRecordController::class, 'store']);

# Vista general de inspecciones: equipos y máquinas

Route::middleware(['auth:sanctum', 'permission:inspection.view'])->get('/inspection-overview/equipment', [InspectionOverviewController::class, 'equipment']);

Route::middleware(['auth:sanctum', 'permission:inspection.view'])->get('/inspection-overview/machines', [InspectionOverviewController::class, 'machines']);

# Exportaciones unitarias Medición de gases
Route::middleware(['auth:sanctum', 'permission:gas-measurements.view'])->get('/gas-measurements/{id}/export/excel', [GasMeasurementExportController::class, 'unitaryGasExcel']);
Route::middleware(['auth:sanctum', 'permission:gas-measurements.view'])->get('/gas-measurements/{id}/export/pdf', [GasMeasurementExportController::class, 'unitaryGasPdf']);

# Exportaciones por rango de fechas Medición de gases
Route::middleware(['auth:sanctum', 'permission:gas-measurements.view'])->get('/gas-measurements/export/range/excel', [GasMeasurementExportController::class, 'rangeGasExcel']);
Route::middleware(['auth:sanctum', 'permission:gas-measurements.view'])->get('/gas-measurements/export/range/pdf', [GasMeasurementExportController::class, 'rangeGasPdf']);

# Exportaciones unitarias para Ficha técnica Equipos
Route::middleware('auth:sanctum', 'permission:equipment.view')->get('/equipment/{id}/export/excel', [TechnicalSheetExportController::class, 'equipmentExcel']);
Route::middleware('auth:sanctum', 'permission:equipment.view')->get('/equipment/{id}/export/pdf', [TechnicalSheetExportController::class, 'equipmentPdf']);

# Exportaciones unitarias para Ficha técnica Máquinas
Route::middleware('auth:sanctum', 'permission:machine.view')->get('/machine/{id}/export/excel', [TechnicalSheetExportController::class, 'machineExcel']);
Route::middleware('auth:sanctum', 'permission:machine.view')->get('/machine/{id}/export/pdf', [TechnicalSheetExportController::class, 'machinePdf']);

# Exportaciones unitarias para Uso diario
Route::middleware(['auth:sanctum', 'permission:daily-use.view'])->get('/dailyuse/{id}/export/excel', [DailyUseExportController::class, 'dailyUseExcel']);
Route::middleware(['auth:sanctum', 'permission:daily-use.view'])->get('/dailyuse/{id}/export/pdf', [DailyUseExportController::class, 'dailyUsePdf']);

# Exportaciones unitarias para Inspección de equipos
Route::middleware(['auth:sanctum', 'permission:inspection.view'])->get('/inspection-equipment/{id}/export/excel', [InspectionEquipmentExportController::class, 'inspectionEquipmentExcel']);
Route::middleware(['auth:sanctum', 'permission:inspection.view'])->get('/inspection-equipment/{id}/export/pdf', [InspectionEquipmentExportController::class, 'inspectionEquipmentPdf']);

# Exportaciones por rango de fechas para Inspección de equipos
Route::middleware(['auth:sanctum', 'permission:inspection.view'])->get('/equipment/{equipmentId}/inspection-equipment/export/range/excel', [InspectionEquipmentExportController::class, 'rangeInspectionEquipmentExcel']);
Route::middleware(['auth:sanctum', 'permission:inspection.view'])->get('/equipment/{equipmentId}/inspection-equipment/export/range/pdf', [InspectionEquipmentExportController::class, 'rangeInspectionEquipmentPdf']);

# Exportaciones unitarias para Inspección de máquinas
Route::middleware(['auth:sanctum', 'permission:inspection.view'])->get('/inspection-machine/{id}/export/excel', [InspectionMachineExportController::class, 'inspectionMachineExcel']);
Route::middleware(['auth:sanctum', 'permission:inspection.view'])->get('/inspection-machine/{id}/export/pdf', [InspectionMachineExportController::class, 'inspectionMachinePdf']);

# Exportaciones por rango de fechas para Inspección de máquinas
Route::middleware(['auth:sanctum', 'permission:inspection.view'])->get('/machines/{machineId}/inspection-machine/export/range/excel', [InspectionMachineExportController::class, 'rangeInspectionMachineExcel']);
Route::middleware(['auth:sanctum', 'permission:inspection.view'])->get('/machines/{machineId}/inspection-machine/export/range/pdf', [InspectionMachineExportController::class, 'rangeInspectionMachinePdf']);

# Exportaciones unitarias para Preoperacional de máquinas
Route::middleware(['auth:sanctum', 'permission:inspection.view'])->get('/preoperational-machine/{id}/export/excel', [PreoperationalMachineExportController::class, 'preoperationalMachineExcel']);
Route::middleware(['auth:sanctum', 'permission:inspection.view'])->get('/preoperational-machine/{id}/export/pdf', [PreoperationalMachineExportController::class, 'preoperationalMachinePdf']);

# Exportaciones por rango de fechas para Preoperacional de máquinas
Route::middleware(['auth:sanctum', 'permission:inspection.view'])->get('/machines/{machineId}/preoperational-machine/export/range/excel', [PreoperationalMachineExportController::class, 'rangePreoperationalMachineExcel']);
Route::middleware(['auth:sanctum', 'permission:inspection.view'])->get('/machines/{machineId}/preoperational-machine/export/range/pdf', [PreoperationalMachineExportController::class, 'rangePreoperationalMachinePdf']);
