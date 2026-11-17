<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use Database\Seeders\RoleAndAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Clave de backups, backup general, historial de cambios y reversión. */
class BackupsYHistorialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndAdminSeeder::class);
        config(['services.backups.admin_key' => 'ClaveInterna2026']);
        Storage::fake('local');
    }

    private function usuarioConRol(string $rol): User
    {
        $user = User::forceCreate([
            'name' => $rol,
            'last_name' => 'Prueba',
            'email' => str_replace(' ', '', mb_strtolower($rol)) . '@mantia.test',
            'password' => Hash::make('Segura123'),
            'status' => 'approved',
            'is_active' => true,
        ]);
        $user->assignRole($rol);

        return $user;
    }

    private function crearUbicacion(): int
    {
        $categoria = DB::table('location_category')->insertGetId(['name' => 'Bodegas', 'description' => 'x']);

        return DB::table('location')->insertGetId([
            'name' => 'Bodega central', 'description' => 'x', 'status' => 'active', 'location_category_id' => $categoria,
        ]);
    }

    // ---------------- Backups ----------------

    public function test_la_primera_copia_exige_la_contrasena_de_inicio_de_sesion(): void
    {
        $jefe = $this->usuarioConRol('Jefe de mina');

        $this->actingAs($jefe)->postJson('/api/admin/backups', ['password' => 'Incorrecta123'])
            ->assertStatus(422)->assertJsonValidationErrors('password');

        $this->actingAs($jefe)->postJson('/api/admin/backups', ['password' => 'Segura123'])
            ->assertCreated()->assertJsonPath('backup.tipo', 'general');
    }

    public function test_las_acciones_posteriores_fallan_si_no_hay_clave_administrativa(): void
    {
        config(['services.backups.admin_key' => null]);
        $jefe = $this->usuarioConRol('Jefe de mina');

        $this->actingAs($jefe)->postJson('/api/admin/backups', ['password' => 'Segura123'])->assertCreated();

        $this->actingAs($jefe)->postJson('/api/admin/backups', ['clave_admin' => 'ClaveInterna2026'])
            ->assertStatus(503)
            ->assertJsonPath('codigo', 'clave_admin_no_configurada');
    }

    public function test_las_copias_siguientes_y_eliminar_exigen_la_clave_del_servidor(): void
    {
        $jefe = $this->usuarioConRol('Jefe de mina');

        $this->actingAs($jefe)->postJson('/api/admin/backups', ['password' => 'Segura123'])->assertCreated();

        $this->actingAs($jefe)->postJson('/api/admin/backups', ['clave_admin' => 'Incorrecta1'])
            ->assertStatus(422)->assertJsonValidationErrors('clave_admin');

        $respuesta = $this->actingAs($jefe)->postJson('/api/admin/backups', ['clave_admin' => 'ClaveInterna2026'])
            ->assertCreated()
            ->assertJsonPath('backup.tipo', 'general');

        $incluye = $respuesta->json('backup.incluye');
        $this->assertContains('users', $incluye);
        $this->assertContains('roles', $incluye);
        $this->assertNotContains('sessions', $incluye);
        $this->assertNotContains('activity_logs', $incluye);

        $archivo = $respuesta->json('backup.archivo');

        $this->actingAs($jefe)->postJson("/api/admin/backups/{$archivo}/download", ['clave_admin' => 'mal'])->assertStatus(422);
        $this->actingAs($jefe)->postJson("/api/admin/backups/{$archivo}/download", ['clave_admin' => 'ClaveInterna2026'])->assertOk();

        $this->actingAs($jefe)->deleteJson("/api/admin/backups/{$archivo}", ['clave_admin' => 'mal'])
            ->assertStatus(422)->assertJsonValidationErrors('clave_admin');
        $this->actingAs($jefe)->deleteJson("/api/admin/backups/{$archivo}", ['clave_admin' => 'ClaveInterna2026'])->assertOk();
    }

    public function test_restaurar_backup_general_con_clave(): void
    {
        $jefe = $this->usuarioConRol('Jefe de mina');
        $this->crearUbicacion();

        $this->actingAs($jefe)->postJson('/api/admin/backups', ['password' => 'Segura123'])->assertCreated();
        $archivo = $this->actingAs($jefe)->postJson('/api/admin/backups', ['clave_admin' => 'ClaveInterna2026'])->json('backup.archivo');

        DB::table('location')->update(['name' => 'Cambiado']);

        $this->actingAs($jefe)->postJson("/api/admin/backups/{$archivo}/restore", ['clave_admin' => 'ClaveInterna2026'])->assertOk();

        $this->assertDatabaseHas('location', ['name' => 'Bodega central']);
    }

    public function test_la_clave_se_bloquea_tras_5_intentos(): void
    {
        $jefe = $this->usuarioConRol('Jefe de mina');
        $this->actingAs($jefe)->postJson('/api/admin/backups', ['password' => 'Segura123'])->assertCreated();

        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($jefe)->postJson('/api/admin/backups', ['clave_admin' => 'Mala' . $i])->assertStatus(422);
        }

        $this->actingAs($jefe)->postJson('/api/admin/backups', ['clave_admin' => 'ClaveInterna2026'])->assertStatus(429);
    }

    // ---------------- Historial ----------------

    public function test_historial_registra_cambios_y_superadmin_revierte(): void
    {
        $almacenista = $this->usuarioConRol('Almacenista');
        $super = $this->usuarioConRol('Superadministrador');
        $jefe = $this->usuarioConRol('Jefe de mina');

        $ubicacion = $this->crearUbicacion();
        $grupo = DB::table('category_group')->insertGetId(['name' => 'Consumibles']);
        $categoria = DB::table('category')->insertGetId(['name' => 'Manuales', 'description' => 'x', 'status' => 'active', 'category_group_id' => $grupo]);

        $herramienta = $this->actingAs($almacenista)->postJson('/api/tools', [
            'name' => 'Martillo', 'location_id' => $ubicacion, 'category_id' => $categoria,
            'condiciones' => ['bueno' => 10, 'regular' => 0, 'malo' => 0],
        ])->assertCreated()->json('id');

        // El almacenista se equivoca en la cantidad
        $this->actingAs($almacenista)->putJson("/api/tools/{$herramienta}", [
            'name' => 'Martillo', 'location_id' => $ubicacion, 'category_id' => $categoria,
            'condiciones' => ['bueno' => 100, 'regular' => 0, 'malo' => 0],
        ])->assertOk();

        $log = ActivityLog::where('auditable_type', \App\Models\ToolStockCondition::class)
            ->where('action', 'updated')->latest('id')->firstOrFail();

        $this->assertSame('Almacenista', $log->user_role);
        $this->assertEquals(10, $log->old_values['stock']);

        // El Jefe de mina lo ve, pero no puede revertir
        $this->actingAs($jefe)->getJson('/api/admin/activity')->assertOk()->assertJsonPath('puede_revertir', false);
        $this->actingAs($jefe)->postJson("/api/admin/activity/{$log->id}/revert", ['password' => 'Segura123'])->assertForbidden();

        // El Superadministrador revierte (pide su contraseña)
        $this->actingAs($super)->postJson("/api/admin/activity/{$log->id}/revert", ['password' => 'mala'])->assertStatus(422);
        $this->actingAs($super)->postJson("/api/admin/activity/{$log->id}/revert", ['password' => 'Segura123'])->assertOk();

        $this->assertDatabaseHas('tool_stock_condition', ['tool_id' => $herramienta, 'condition' => 'bueno', 'stock' => 10]);
        $this->assertNotNull($log->fresh()->reverted_at);

        // No se puede revertir dos veces
        $this->actingAs($super)->postJson("/api/admin/activity/{$log->id}/revert", ['password' => 'Segura123'])->assertStatus(422);
    }

    public function test_revertir_eliminacion_recupera_registro_y_dependientes(): void
    {
        $almacenista = $this->usuarioConRol('Almacenista');
        $super = $this->usuarioConRol('Superadministrador');

        $ubicacion = $this->crearUbicacion();
        $grupo = DB::table('category_group')->insertGetId(['name' => 'Consumibles']);
        $categoria = DB::table('category')->insertGetId(['name' => 'Manuales', 'description' => 'x', 'status' => 'active', 'category_group_id' => $grupo]);

        $herramienta = $this->actingAs($almacenista)->postJson('/api/tools', [
            'name' => 'Taladro', 'location_id' => $ubicacion, 'category_id' => $categoria,
            'condiciones' => ['bueno' => 3, 'regular' => 1, 'malo' => 0],
        ])->assertCreated()->json('id');

        $this->actingAs($almacenista)->deleteJson("/api/tools/{$herramienta}")->assertOk();
        $this->assertDatabaseMissing('tool', ['id' => $herramienta]);

        $log = ActivityLog::where('auditable_type', \App\Models\Tools::class)->where('action', 'deleted')->firstOrFail();

        $this->actingAs($super)->postJson("/api/admin/activity/{$log->id}/revert", ['password' => 'Segura123'])->assertOk();

        $this->assertDatabaseHas('tool', ['id' => $herramienta, 'name' => 'Taladro']);
        $this->assertSame(4, (int) DB::table('tool_stock_condition')->where('tool_id', $herramienta)->sum('stock'));
    }

    public function test_jefe_no_ve_cambios_del_superadmin(): void
    {
        $super = $this->usuarioConRol('Superadministrador');
        $jefe = $this->usuarioConRol('Jefe de mina');

        $this->actingAs($super)->postJson('/api/location-category', ['nombre' => 'Talleres', 'descripcion' => 'Talleres de la mina'])->assertSuccessful();

        $this->assertGreaterThan(0, ActivityLog::where('user_role', 'Superadministrador')->count());

        $descripciones = collect($this->actingAs($jefe)->getJson('/api/admin/activity')->json('cambios'))->pluck('rol');
        $this->assertNotContains('Superadministrador', $descripciones);

        $descripciones = collect($this->actingAs($super)->getJson('/api/admin/activity')->json('cambios'))->pluck('rol');
        $this->assertContains('Superadministrador', $descripciones);
    }

    public function test_solo_superadmin_ve_el_corredor_de_pruebas(): void
    {
        $jefe = $this->usuarioConRol('Jefe de mina');
        $super = $this->usuarioConRol('Superadministrador');

        $this->actingAs($jefe)->getJson('/api/admin/tests')->assertForbidden();
        $this->actingAs($super)->getJson('/api/admin/tests')->assertOk()->assertJsonStructure(['disponible', 'resultado']);
    }
}
