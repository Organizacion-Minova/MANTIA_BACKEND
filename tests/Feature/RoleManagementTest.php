<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleAndAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleAndAdminSeeder::class);
    }

    private function jefeDeMina(): User
    {
        $user = User::forceCreate([
            'name' => 'Jefe',
            'last_name' => 'Prueba',
            'email' => 'jefe.roles@mantia.test',
            'password' => Hash::make('Segura123'),
            'status' => 'approved',
            'is_active' => true,
        ]);
        $user->assignRole('Jefe de mina');

        return $user;
    }

    public function test_catalogo_de_permisos_incluye_acciones_y_descripciones(): void
    {
        $jefe = $this->jefeDeMina();

        $respuesta = $this->actingAs($jefe)->getJson('/api/admin/permissions')->assertOk();
        $grupos = $respuesta->json('grupos');

        $this->assertCount(10, $grupos);
        foreach ($grupos as $grupo) {
            $this->assertCount($grupo['modulo'] === 'inspection' ? 3 : 4, $grupo['permisos']);
            foreach ($grupo['permisos'] as $permiso) {
                $this->assertNotEmpty($permiso['descripcion']);
            }
        }
        $inspecciones = collect($grupos)->firstWhere('modulo', 'inspection');
        $this->assertNotContains('inspection.delete', array_column($inspecciones['permisos'], 'name'));
    }

    public function test_se_puede_crear_un_rol_con_permisos_seleccionados(): void
    {
        $jefe = $this->jefeDeMina();

        $this->actingAs($jefe)->postJson('/api/admin/roles', [
            'name' => 'Supervisor',
            'permissions' => ['machine.view', 'machine.create'],
        ])->assertCreated()
            ->assertJsonPath('role.name', 'Supervisor')
            ->assertJsonCount(2, 'role.permissions');

        $this->assertDatabaseHas('roles', ['name' => 'Supervisor']);
    }
}
