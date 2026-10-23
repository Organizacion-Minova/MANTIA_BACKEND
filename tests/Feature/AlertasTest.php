<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\Machine;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Pruebas del sistema de alertas (notificaciones de la campana).
 *
 * Cada prueba hace una acción y revisa que se guardó la notificación
 * correcta en la tabla `notifications` (tipo, nivel y a quién le llegó).
 *
 * Correr solo estas pruebas:  php artisan test --filter=AlertasTest
 */
class AlertasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $tecnico;
    private array $ref;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Administrador']);
        Role::firstOrCreate(['name' => 'Tecnico']);

        $this->admin = User::factory()->create(['email' => 'admin@mantia.test', 'password' => Hash::make('secreto123'), 'status' => 'approved']);
        $this->admin->assignRole('Administrador');

        $this->tecnico = User::factory()->create(['email' => 'tecnico@mantia.test', 'status' => 'approved']);
        $this->tecnico->assignRole('Tecnico');

        $this->ref = $this->crearReferencias();
        DB::table('notifications')->delete(); // empezar limpio
    }

    // ───────────────────────── Ayudantes ─────────────────────────

    private function crearReferencias(): array
    {
        $company = DB::table('company')->insertGetId([
            'name' => 'Minova', 'phone' => '123456', 'email' => 'minova@test.com',
            'address' => 'Mina', 'nit' => '900123456-1',
        ]);
        $machineCategory = DB::table('machine_category')->insertGetId([
            'name' => 'Bombas', 'description' => 'Bombeo', 'status' => 'active',
        ]);
        $mina = DB::table('location_category')->insertGetId(['name' => 'Mina', 'description' => 'Interior mina']);
        $location = DB::table('location')->insertGetId([
            'name' => 'Frente 1', 'description' => 'Frente principal',
            'status' => 'active', 'location_category_id' => $mina,
        ]);
        $group = DB::table('category_group')->insertGetId(['name' => 'Herramientas manuales']);
        $category = DB::table('category')->insertGetId([
            'name' => 'Llaves', 'description' => 'Llaves fijas', 'status' => 'active', 'category_group_id' => $group,
        ]);

        return compact('company', 'machineCategory', 'location', 'group', 'category');
    }

    /** Notificaciones guardadas para un usuario, como arreglo simple. */
    private function alertasDe(User $u): array
    {
        return $u->fresh()->notifications()->get()->pluck('data')->all();
    }

    private function assertAlerta(User $u, string $tipo, ?string $nivel = null, ?string $contiene = null): void
    {
        $ok = collect($this->alertasDe($u))->contains(fn ($d) => $d['tipo'] === $tipo
            && ($nivel === null || $d['nivel'] === $nivel)
            && ($contiene === null || str_contains($d['mensaje'], $contiene)));

        $this->assertTrue($ok, "No llegó la alerta [$tipo" . ($nivel ? "/$nivel" : '') . "]"
            . ($contiene ? " con texto «{$contiene}»" : '')
            . " a {$u->email}. Recibió: " . json_encode($this->alertasDe($u), JSON_UNESCAPED_UNICODE));
    }

    private function datosMaquina(array $extra = []): array
    {
        return array_merge([
            'code' => 'M-001', 'name' => 'Bomba principal', 'model' => 'X1',
            'serial_number' => 'SER-1', 'acquisition_date' => '2025-01-15',
            'acquisition_cost' => 15000, 'status' => 'active', 'warranty' => 12,
            'location_id' => $this->ref['location'], 'machine_category_id' => $this->ref['machineCategory'],
            'company_id' => $this->ref['company'], 'responsible' => 'Juan',
            'machine_usage' => 'Desague', 'in_operation' => 'true', 'characteristics' => 'Bomba sumergible',
        ], $extra);
    }

    private function crearMaquina(array $extra = []): Machine
    {
        return Machine::create(array_merge([
            'code' => 'M-010', 'name' => 'Compresor', 'model' => 'C1', 'serial_number' => 'S-10',
            'acquisition_date' => '2025-01-15', 'acquisition_cost' => 1000, 'status' => 'active',
            'warranty' => 12, 'location_id' => $this->ref['location'],
            'machine_category_id' => $this->ref['machineCategory'], 'company_id' => $this->ref['company'],
            'responsible' => 'Ana', 'machine_usage' => 'Aire', 'in_operation' => true,
            'characteristics' => 'N/A',
        ], $extra));
    }

    // ───────────────────── 1. Sesión ─────────────────────

    public function test_01_login_genera_alerta(): void
    {
        $this->withHeader('Origin', 'http://localhost:5173')
            ->postJson('/api/login', ['email' => 'admin@mantia.test', 'password' => 'secreto123'])
            ->assertOk();

        $this->assertAlerta($this->admin, 'login', 'info');
    }

    public function test_02_logout_genera_alerta(): void
    {
        $this->withHeader('Origin', 'http://localhost:5173')
            ->postJson('/api/login', ['email' => 'admin@mantia.test', 'password' => 'secreto123'])
            ->assertOk();
        $this->withHeader('Origin', 'http://localhost:5173')->postJson('/api/logout')->assertOk();

        $this->assertAlerta($this->admin, 'logout', 'info');
    }

    // ───────────────────── 2. Máquinas ─────────────────────

    public function test_03_crear_maquina_por_api_responde_201_y_alerta(): void
    {
        $this->actingAs($this->admin);

        $this->postJson('/api/machines', $this->datosMaquina())->assertCreated();

        $this->assertAlerta($this->admin, 'maquina', 'info', 'Se registró');
        $this->assertAlerta($this->tecnico, 'maquina', 'info'); // llega a todos
    }

    public function test_04_maquina_a_mantenimiento_es_warning(): void
    {
        $m = $this->crearMaquina();
        $m->update(['status' => 'maintenance']);
        $this->assertAlerta($this->admin, 'maquina', 'warning', 'maintenance');
    }

    public function test_05_maquina_inactiva_es_danger(): void
    {
        $m = $this->crearMaquina();
        $m->update(['status' => 'inactive']);
        $this->assertAlerta($this->admin, 'maquina', 'danger');
    }

    public function test_06_maquina_fuera_de_operacion_es_warning(): void
    {
        $m = $this->crearMaquina();
        $m->update(['in_operation' => false]);
        $this->assertAlerta($this->admin, 'maquina', 'warning', 'fuera de operación');
    }

    public function test_07_eliminar_maquina_alerta(): void
    {
        $m = $this->crearMaquina();
        $m->delete();
        $this->assertAlerta($this->admin, 'maquina', 'warning', 'Se eliminó');
    }

    // ───────────────────── 3. Equipos ─────────────────────

    public function test_08_equipo_creado_y_cambio_de_estado(): void
    {
        $e = Equipment::create([
            'code' => 'EQ-1', 'name' => 'Detector', 'description' => 'Multigas',
            'serial_number' => 'SN-1', 'responsible' => 'Ana', 'status' => 'active',
            'registration_date' => '2025-01-01', 'company_id' => $this->ref['company'],
            'location_id' => $this->ref['location'], 'model' => 'D1', 'image' => 'x.jpg',
        ]);
        $this->assertAlerta($this->admin, 'equipo', 'info', 'Se registró');

        $e->update(['status' => 'maintenance']);
        $this->assertAlerta($this->admin, 'equipo', 'warning');
    }

    // ───────────────────── 4. Catálogos ─────────────────────

    public function test_09_empresa_por_api(): void
    {
        $this->actingAs($this->admin);
        $this->postJson('/api/company', [
            'nit' => '800111222-3', 'nombre' => 'Proveedor SAS', 'telefono' => '3001112233',
            'correo' => 'prov@test.com', 'direccion' => 'Calle 1',
        ])->assertSuccessful();

        $this->assertAlerta($this->admin, 'empresa', 'info');
    }

    public function test_10_categoria_de_ubicacion_por_api(): void
    {
        $this->actingAs($this->admin);
        $this->postJson('/api/location-category', ['nombre' => 'Superficie', 'descripcion' => 'Exterior'])
            ->assertSuccessful();
        $this->assertAlerta($this->admin, 'categoria', 'info');
    }

    public function test_11_ubicacion_por_api(): void
    {
        $this->actingAs($this->admin);
        $catId = DB::table('location_category')->value('id');
        $this->postJson('/api/location', ['nombre' => 'Frente 2', 'descripcion' => 'Nuevo', 'categoria' => $catId])
            ->assertSuccessful();
        $this->assertAlerta($this->admin, 'ubicacion', 'info');
    }

    public function test_12_categoria_de_maquina_por_api(): void
    {
        $this->actingAs($this->admin);
        $this->postJson('/api/machine-categories', ['name' => 'Compresores', 'description' => 'Aire comprimido'])->assertSuccessful();
        $this->assertAlerta($this->admin, 'categoria', 'info');
    }

    public function test_13_categoria_de_herramienta_por_api(): void
    {
        $this->actingAs($this->admin);
        $this->postJson('/api/category-tools', [
            'name' => 'Destornilladores', 'description' => 'Planos y estrella', 'status' => 'active', 'category_group_id' => $this->ref['group'],
        ])->assertSuccessful();
        $this->assertAlerta($this->admin, 'categoria', 'info');
    }

    // ───────────────────── 5. Herramientas ─────────────────────

    private function crearHerramienta(int $bueno, int $regular, int $malo)
    {
        return $this->postJson('/api/tools', [
            'name' => 'Llave 10mm', 'location_id' => $this->ref['location'],
            'category_id' => $this->ref['category'],
            'condiciones' => ['bueno' => $bueno, 'regular' => $regular, 'malo' => $malo],
        ]);
    }

    public function test_14_herramienta_creada_alerta(): void
    {
        $this->actingAs($this->admin);
        $this->crearHerramienta(10, 0, 0)->assertSuccessful();
        $this->assertAlerta($this->admin, 'herramienta', 'info', 'Se registró');
    }

    public function test_15_stock_bajo_de_herramienta_alerta(): void
    {
        $this->actingAs($this->admin);
        $id = $this->crearHerramienta(10, 0, 0)->assertSuccessful()->json('id');

        $this->putJson("/api/tools/{$id}", [
            'name' => 'Llave 10mm', 'location_id' => $this->ref['location'],
            'category_id' => $this->ref['category'],
            'condiciones' => ['bueno' => 1, 'regular' => 0, 'malo' => 0],
        ])->assertOk();

        $this->assertAlerta($this->admin, 'herramienta', 'warning', 'Stock bajo');
    }

    public function test_16_sin_stock_de_herramienta_es_danger(): void
    {
        $this->actingAs($this->admin);
        $id = $this->crearHerramienta(5, 0, 0)->assertSuccessful()->json('id');

        $this->putJson("/api/tools/{$id}", [
            'name' => 'Llave 10mm', 'location_id' => $this->ref['location'],
            'category_id' => $this->ref['category'],
            'condiciones' => ['bueno' => 0, 'regular' => 0, 'malo' => 0],
        ])->assertOk();

        $this->assertAlerta($this->admin, 'herramienta', 'danger');
    }

    public function test_17_herramientas_en_mal_estado_alerta(): void
    {
        $this->actingAs($this->admin);
        $id = $this->crearHerramienta(10, 0, 0)->assertSuccessful()->json('id');

        $this->putJson("/api/tools/{$id}", [
            'name' => 'Llave 10mm', 'location_id' => $this->ref['location'],
            'category_id' => $this->ref['category'],
            'condiciones' => ['bueno' => 7, 'regular' => 0, 'malo' => 3],
        ])->assertOk();

        $this->assertAlerta($this->admin, 'herramienta', 'warning', 'mal estado');
    }

    // ───────────────────── 6. Gases (lo más crítico) ─────────────────────

    private function crearMedicionGas(): int
    {
        $this->actingAs($this->admin);
        $res = $this->postJson('/api/gas-measurements', ['measurement_time' => '07:30']);
        $res->assertCreated();
        return $res->json('data.detail.0.id');
    }

    public function test_18_crear_medicion_de_gas_responde_201(): void
    {
        $this->crearMedicionGas();
        $this->assertDatabaseCount('gas_measurement', 1);
    }

    public function test_19_oxigeno_bajo_genera_alerta_danger_al_admin(): void
    {
        $detalle = $this->crearMedicionGas();
        DB::table('notifications')->delete();

        $this->putJson("/api/gas-measurements/detail/{$detalle}", ['oxygen' => 17.0])->assertOk();

        $this->assertAlerta($this->admin, 'gas', 'danger', 'Oxígeno');
        $this->assertAlerta($this->admin, 'gas', 'danger', 'Frente 1'); // dice la ubicación
    }

    public function test_20_cada_gas_fuera_de_limite_se_detecta(): void
    {
        $detalle = $this->crearMedicionGas();
        DB::table('notifications')->delete();

        $this->putJson("/api/gas-measurements/detail/{$detalle}", [
            'oxygen' => 20.9, 'methane' => 1.5, 'carbon_dioxide' => 0.8,
            'hydrogen_sulfide' => 12, 'carbon_monoxide' => 30, 'nitrogen_dioxide' => 4,
        ])->assertOk();

        foreach (['Metano', 'Dióxido de carbono', 'Ácido sulfhídrico', 'Monóxido de carbono', 'Dióxido de nitrógeno'] as $gas) {
            $this->assertAlerta($this->admin, 'gas', 'danger', $gas);
        }
    }

    public function test_21_gases_normales_no_generan_alerta_de_peligro(): void
    {
        $detalle = $this->crearMedicionGas();
        DB::table('notifications')->delete();

        $this->putJson("/api/gas-measurements/detail/{$detalle}", [
            'oxygen' => 20.9, 'methane' => 0.1, 'carbon_dioxide' => 0.1,
            'hydrogen_sulfide' => 1, 'carbon_monoxide' => 2, 'nitrogen_dioxide' => 0.5,
        ])->assertOk();

        $peligros = collect($this->alertasDe($this->admin))->where('nivel', 'danger');
        $this->assertCount(0, $peligros, 'Hubo alertas de peligro con valores normales');
    }

    public function test_22_alerta_de_gas_solo_llega_al_administrador(): void
    {
        $detalle = $this->crearMedicionGas();
        DB::table('notifications')->delete();

        $this->putJson("/api/gas-measurements/detail/{$detalle}", ['methane' => 5])->assertOk();

        $this->assertAlerta($this->admin, 'gas', 'danger');
        $gasTecnico = collect($this->alertasDe($this->tecnico))->where('tipo', 'gas');
        $this->assertCount(0, $gasTecnico, 'La alerta de gas le llegó a un técnico');
    }

    public function test_23_limite_exacto_no_dispara_alerta(): void
    {
        $detalle = $this->crearMedicionGas();
        DB::table('notifications')->delete();

        // Justo en el límite permitido: NO es riesgo
        $this->putJson("/api/gas-measurements/detail/{$detalle}", [
            'oxygen' => 19.5, 'methane' => 1.0, 'carbon_monoxide' => 25,
        ])->assertOk();

        $this->assertCount(0, collect($this->alertasDe($this->admin))->where('nivel', 'danger'));
    }

    // ───────────────────── 7. Inspecciones ─────────────────────

    public function test_24_existe_ruta_para_guardar_inspecciones(): void
    {
        $existe = collect(Route::getRoutes())->contains(
            fn ($r) => str_starts_with($r->uri(), 'api/inspection') && in_array('POST', $r->methods())
        );
        if (!$existe) {
            // Pendiente: el formulario de inspección no guarda en el backend todavía
            $this->markTestIncomplete('No hay ruta POST /api/inspections: la alerta de inspección nunca se puede disparar');
        }
        $this->assertTrue($existe);
    }

    // ───────────────────── 8. Endpoints de la campana ─────────────────────

    public function test_25_listar_y_marcar_notificaciones(): void
    {
        $this->admin->notify(new \App\Notifications\AlertaSistema('maquina', 'Prueba 1', 'info'));
        $this->admin->notify(new \App\Notifications\AlertaSistema('gas', 'Prueba 2', 'danger'));
        $this->actingAs($this->admin);

        $lista = $this->getJson('/api/notificaciones')->assertOk()->assertJsonCount(2)->json();
        $this->assertArrayHasKey('tipo', $lista[0]['data']);
        $this->assertArrayHasKey('nivel', $lista[0]['data']);

        $this->postJson("/api/notificaciones/{$lista[0]['id']}/leer")->assertOk();
        $this->assertSame(1, $this->admin->fresh()->unreadNotifications()->count());

        $this->postJson('/api/notificaciones/leer-todas')->assertOk();
        $this->assertSame(0, $this->admin->fresh()->unreadNotifications()->count());
    }

    public function test_26_un_usuario_no_puede_marcar_notificacion_ajena(): void
    {
        $this->admin->notify(new \App\Notifications\AlertaSistema('maquina', 'Privada', 'info'));
        $id = $this->admin->notifications()->first()->id;

        $this->actingAs($this->tecnico);
        $this->postJson("/api/notificaciones/{$id}/leer")->assertNotFound();
    }

    public function test_27_campana_requiere_sesion(): void
    {
        $this->getJson('/api/notificaciones')->assertUnauthorized();
    }

    public function test_28_usuarios_pendientes_no_reciben_alertas(): void
    {
        $pendiente = User::factory()->create(['status' => 'pending']);
        $this->crearMaquina();

        $this->assertCount(0, $this->alertasDe($pendiente), 'Un usuario pendiente de aprobación recibió alertas');
    }
}