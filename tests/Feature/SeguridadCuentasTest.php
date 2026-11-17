<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Validaciones de crear cuenta, recuperar contraseña, sesión pública y gestión de usuarios. */
class SeguridadCuentasTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndAdminSeeder::class);
        Notification::fake();
    }

    private function datosRegistro(array $cambios = []): array
    {
        return array_merge([
            'name' => 'Juan Carlos',
            'last_name' => 'Pérez',
            'email' => 'juan@correo.com',
            'password' => 'Segura123',
            'password_confirmation' => 'Segura123',
            'document_type' => 'CC',
            'document_number' => '1020304050',
        ], $cambios);
    }

    private function usuarioConRol(string $rol, array $datos = []): User
    {
        $user = User::forceCreate(array_merge([
            'name' => $rol,
            'last_name' => 'Prueba',
            'email' => str_replace(' ', '', mb_strtolower($rol)) . '@mantia.test',
            'password' => Hash::make('Segura123'),
            'status' => 'approved',
            'is_active' => true,
        ], $datos));
        $user->assignRole($rol);

        return $user;
    }

    public function test_registro_valido_crea_visitante(): void
    {
        $this->postJson('/api/register', $this->datosRegistro())->assertCreated();

        $user = User::where('email', 'juan@correo.com')->firstOrFail();
        $this->assertTrue($user->hasRole('Visitante'));
    }

    public function test_cedula_no_acepta_letras_ni_mas_de_10_digitos(): void
    {
        $this->postJson('/api/register', $this->datosRegistro(['document_number' => '10203A4050']))
            ->assertStatus(422)->assertJsonValidationErrors('document_number');

        $this->postJson('/api/register', $this->datosRegistro(['document_number' => '12345678901']))
            ->assertStatus(422)->assertJsonValidationErrors('document_number');
    }

    public function test_pasaporte_si_acepta_letras(): void
    {
        $this->postJson('/api/register', $this->datosRegistro(['document_type' => 'PA', 'document_number' => 'ab123456']))
            ->assertCreated();

        $this->assertDatabaseHas('users', ['document_number' => 'AB123456']);
    }

    public function test_nombre_solo_letras_y_contrasena_segura(): void
    {
        $this->postJson('/api/register', $this->datosRegistro(['name' => 'Juan123']))
            ->assertStatus(422)->assertJsonValidationErrors('name');

        $this->postJson('/api/register', $this->datosRegistro(['password' => 'sololetras', 'password_confirmation' => 'sololetras']))
            ->assertStatus(422)->assertJsonValidationErrors('password');

        $this->postJson('/api/register', $this->datosRegistro(['password_confirmation' => 'Otra12345']))
            ->assertStatus(422)->assertJsonValidationErrors('password');
    }

    public function test_correo_repetido_sin_importar_mayusculas(): void
    {
        $this->postJson('/api/register', $this->datosRegistro())->assertCreated();

        $this->postJson('/api/register', $this->datosRegistro(['email' => 'JUAN@correo.com', 'document_number' => '99887766']))
            ->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_codigo_de_recuperacion_solo_6_numeros(): void
    {
        $base = ['email' => 'a@b.com', 'password' => 'Segura123', 'password_confirmation' => 'Segura123'];

        $this->postJson('/api/reset-password', $base + ['code' => '12a456'])
            ->assertStatus(422)->assertJsonValidationErrors('code');

        $this->postJson('/api/reset-password', $base + ['code' => '1234567'])
            ->assertStatus(422)->assertJsonValidationErrors('code');
    }

    public function test_recuperar_contrasena_completo(): void
    {
        $user = $this->usuarioConRol('Almacenista');

        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make('123456'),
            'created_at' => now(),
        ]);

        $this->postJson('/api/reset-password', [
            'email' => strtoupper($user->email),
            'code' => '123456',
            'password' => 'NuevaClave9',
            'password_confirmation' => 'NuevaClave9',
        ])->assertOk();

        $this->assertTrue(Hash::check('NuevaClave9', $user->fresh()->password));
    }

    public function test_sesion_publica_no_devuelve_401(): void
    {
        $this->getJson('/api/session')->assertOk()->assertJsonPath('user', null);
    }

    public function test_login_por_letra_no_esta_disponible(): void
    {
        $this->postJson('/api/dev-login/A')->assertNotFound();
    }

    public function test_seeder_de_roles_no_crea_una_cuenta_admin_con_clave_predeterminada(): void
    {
        $this->assertDatabaseMissing('users', ['email' => 'MantiaCM@gmail.com']);
    }

    public function test_superadmin_recibe_todos_los_permisos(): void
    {
        $super = $this->usuarioConRol('Superadministrador');

        $permisos = $this->actingAs($super)->getJson('/api/user')->assertOk()->json('permission_names');

        $this->assertContains('tool.delete', $permisos);
        $this->assertContains('gas-measurements.update', $permisos);
    }

    public function test_jefe_crea_usuario_pero_no_puede_asignar_superadmin(): void
    {
        $jefe = $this->usuarioConRol('Jefe de mina');

        $datos = $this->datosRegistro(['email' => 'nuevo@mantia.test', 'role' => 'Almacenista']);
        $this->actingAs($jefe)->postJson('/api/admin/users', $datos)->assertCreated();
        $this->assertTrue(User::where('email', 'nuevo@mantia.test')->first()->hasRole('Almacenista'));

        $datos = $this->datosRegistro(['email' => 'otro@mantia.test', 'document_number' => '55667788', 'role' => 'Superadministrador']);
        $this->actingAs($jefe)->postJson('/api/admin/users', $datos)->assertForbidden();
    }

    public function test_jefe_no_puede_tocar_cuenta_de_superadmin(): void
    {
        $jefe = $this->usuarioConRol('Jefe de mina');
        $super = $this->usuarioConRol('Superadministrador');

        $this->actingAs($jefe)->putJson("/api/admin/users/{$super->id}/toggle-active")->assertForbidden();
        $this->actingAs($jefe)->putJson("/api/admin/users/{$super->id}/role", ['role' => 'Visitante'])->assertForbidden();
    }

    public function test_visitante_no_puede_llenar_inspecciones(): void
    {
        $visitante = $this->usuarioConRol('Visitante');

        $this->actingAs($visitante)->postJson('/api/inspections', [])->assertForbidden();
        $this->actingAs($visitante)->postJson('/api/inspection-formats-machine', [])->assertForbidden();
    }
}
