<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AlertaSistema;
use App\Services\AlertaExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AlertaExportTest extends TestCase
{
    use RefreshDatabase;

    private User $usuario;
    private User $otro;

    protected function setUp(): void
    {
        parent::setUp();
        config(['alertas.zona_horaria' => 'America/Bogota']);
        $this->usuario = User::factory()->create(['email' => 'uno@mantia.test', 'status' => 'approved']);
        $this->otro = User::factory()->create(['email' => 'dos@mantia.test', 'status' => 'approved']);
    }

    private function crearAlerta(User $usuario, string $momentoUtc, string $nivel = 'info', string $tipo = 'maquina', string $mensaje = 'Se registró: Bomba principal', bool $leida = false): void
    {
        $usuario->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => AlertaSistema::class,
            'data' => ['tipo' => $tipo, 'mensaje' => $mensaje, 'nivel' => $nivel],
            'read_at' => $leida ? $momentoUtc : null,
            'created_at' => $momentoUtc,
            'updated_at' => $momentoUtc,
        ]);
    }

    public function test_el_servicio_trae_solo_las_alertas_del_usuario_con_la_hora_local(): void
    {
        $this->crearAlerta($this->usuario, '2026-11-10 03:00:00', 'warning', 'equipo', 'Se eliminó: Detector');
        $this->crearAlerta($this->usuario, '2026-11-10 14:30:00', 'danger', 'gas', 'Oxígeno fuera de rango', true);
        $this->crearAlerta($this->otro, '2026-11-10 14:30:00', 'info', 'maquina', 'Alerta de otro usuario');

        $servicio = new AlertaExportService();

        $soloDia10 = $servicio->obtenerDatosRango($this->usuario, '2026-11-10', '2026-11-10');
        $this->assertCount(1, $soloDia10);
        $this->assertSame('10/11/2026', $soloDia10[0]['fecha']);
        $this->assertSame('09:30', $soloDia10[0]['hora']);
        $this->assertSame('Medición de gas', $soloDia10[0]['categoria']);
        $this->assertSame('Grave', $soloDia10[0]['nivel']);
        $this->assertSame('Leída', $soloDia10[0]['estado']);

        $dias9y10 = $servicio->obtenerDatosRango($this->usuario, '2026-11-09', '2026-11-10');
        $this->assertCount(2, $dias9y10);
        $this->assertSame('09/11/2026', $dias9y10[0]['fecha']);
        $this->assertSame('22:00', $dias9y10[0]['hora']);
        $this->assertSame('Sin leer', $dias9y10[0]['estado']);
    }

    public function test_el_servicio_filtra_por_nivel(): void
    {
        $this->crearAlerta($this->usuario, '2026-11-10 14:00:00', 'danger');
        $this->crearAlerta($this->usuario, '2026-11-10 15:00:00', 'info');

        $graves = (new AlertaExportService())->obtenerDatosRango($this->usuario, '2026-11-10', '2026-11-10', 'danger');

        $this->assertCount(1, $graves);
        $this->assertSame('Grave', $graves[0]['nivel']);
    }

    public function test_el_servicio_traduce_el_estado_de_alertas_antiguas(): void
    {
        $this->crearAlerta($this->usuario, '2026-11-10 14:00:00', 'danger', 'maquina', 'Bomba cambió a estado: inactive');

        $alertas = (new AlertaExportService())->obtenerDatosRango($this->usuario, '2026-11-10', '2026-11-10');

        $this->assertSame('Bomba cambió a estado: Inactivo', $alertas[0]['mensaje']);
    }

    public function test_exporta_las_alertas_a_excel(): void
    {
        $this->crearAlerta($this->usuario, '2026-11-10 14:30:00');

        $respuesta = $this->actingAs($this->usuario)
            ->get('/api/notificaciones/export/excel?fecha_inicio=2026-11-10&fecha_fin=2026-11-10');

        $respuesta->assertOk();
        $this->assertStringContainsString('Alertas_2026-11-10_al_2026-11-10.xlsx', $respuesta->headers->get('content-disposition'));
    }

    public function test_exporta_las_alertas_a_pdf(): void
    {
        $this->crearAlerta($this->usuario, '2026-11-10 14:30:00', 'warning');

        $respuesta = $this->actingAs($this->usuario)
            ->get('/api/notificaciones/export/pdf?fecha_inicio=2026-11-10&fecha_fin=2026-11-10&nivel=warning');

        $respuesta->assertOk();
        $this->assertStringContainsString('application/pdf', $respuesta->headers->get('content-type'));
    }

    public function test_avisa_cuando_no_hay_alertas_en_el_rango(): void
    {
        $this->crearAlerta($this->otro, '2026-11-10 14:30:00');

        $this->actingAs($this->usuario)
            ->getJson('/api/notificaciones/export/pdf?fecha_inicio=2026-11-10&fecha_fin=2026-11-10')
            ->assertStatus(404)
            ->assertJsonPath('message', 'No hay alertas en el rango seleccionado.');
    }

    public function test_rechaza_rangos_de_mas_de_31_dias(): void
    {
        $this->actingAs($this->usuario)
            ->getJson('/api/notificaciones/export/excel?fecha_inicio=2026-10-01&fecha_fin=2026-11-10')
            ->assertStatus(422)
            ->assertJsonPath('message', 'El rango máximo permitido es de 31 días.');
    }

    public function test_valida_las_fechas_y_el_nivel(): void
    {
        $this->actingAs($this->usuario)
            ->getJson('/api/notificaciones/export/excel?fecha_inicio=2026-11-10&fecha_fin=2026-11-01')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['fecha_fin']);

        $this->actingAs($this->usuario)
            ->getJson('/api/notificaciones/export/excel?fecha_inicio=2026-11-01&fecha_fin=2026-11-10&nivel=otro')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nivel']);
    }

    public function test_pide_sesion_iniciada(): void
    {
        $this->getJson('/api/notificaciones/export/excel?fecha_inicio=2026-11-10&fecha_fin=2026-11-10')
            ->assertStatus(401);
    }

    public function test_el_historial_trae_solo_las_alertas_del_usuario(): void
    {
        $this->crearAlerta($this->usuario, '2026-11-10 14:30:00');
        $this->crearAlerta($this->otro, '2026-11-10 14:30:00');

        $this->actingAs($this->usuario)
            ->getJson('/api/notificaciones/historial')
            ->assertOk()
            ->assertJsonCount(1);
    }
}