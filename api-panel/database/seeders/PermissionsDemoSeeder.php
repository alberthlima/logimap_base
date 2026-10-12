<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use App\Models\User;

class PermissionsDemoSeeder extends Seeder
{
    /**
     * Create the initial roles and permissions.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Dashboard / Escritorio
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'escritorio']);

        // 2. Monitoreo en Mapa
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'ver_mapa']);

        // 3. Clientes Georreferenciados
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'listar_cliente']);
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'registrar_cliente']);
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'editar_cliente']);
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'eliminar_cliente']);

        // 4. Pedidos y Entregas
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'listar_pedido']);
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'registrar_pedido']);
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'editar_pedido']);
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'eliminar_pedido']);

        // 5. Roles y Permisos
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'registrar_rol']);
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'listar_rol']);
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'editar_rol']);
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'eliminar_rol']);

        // 6. Usuarios
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'registrar_usuario']);
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'listar_usuario']);
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'editar_usuario']);
        Permission::firstOrCreate(['guard_name' => 'api', 'name' => 'eliminar_usuario']);

        // Crear Rol Super-Admin
        $role = Role::firstOrCreate(['guard_name' => 'api', 'name' => 'Super-Admin']);

        // Asignar todos los permisos al rol Super-Admin
        $role->givePermissionTo(Permission::all());

        // Crear o actualizar usuario Super-Admin
        $user = User::firstOrCreate(
            ['email' => 'mlima@gmail.com'],
            [
                'name' => 'Super-Admin',
                'surname' => 'Admin',
                'password' => '12345678',
                'role_id' => $role->id,
                'state' => 1,
            ]
        );

        $user->assignRole($role);
    }
}