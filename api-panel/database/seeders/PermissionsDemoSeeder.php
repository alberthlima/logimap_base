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
        Permission::create(['guard_name' => 'api', 'name' => 'escritorio']);

        // 2. Roles y Permisos
        Permission::create(['guard_name' => 'api', 'name' => 'registrar_rol']);
        Permission::create(['guard_name' => 'api', 'name' => 'listar_rol']);
        Permission::create(['guard_name' => 'api', 'name' => 'editar_rol']);
        Permission::create(['guard_name' => 'api', 'name' => 'eliminar_rol']);

        // 3. Usuarios
        Permission::create(['guard_name' => 'api', 'name' => 'registrar_usuario']);
        Permission::create(['guard_name' => 'api', 'name' => 'listar_usuario']);
        Permission::create(['guard_name' => 'api', 'name' => 'editar_usuario']);
        Permission::create(['guard_name' => 'api', 'name' => 'eliminar_usuario']);

        // Crear Rol Super-Admin
        $role = Role::create(['guard_name' => 'api', 'name' => 'Super-Admin']);

        // Asignar todos los permisos al rol Super-Admin
        $role->givePermissionTo(Permission::all());

        // Crear usuario Super-Admin
        $user = User::create([
            'name' => 'Super-Admin',
            'surname' => 'Admin',
            'email' => 'mlima@gmail.com',
            'password' => '12345678', // Se hashea automáticamente por el cast del modelo User
            'role_id' => $role->id,
            'state' => 1,
        ]);

        $user->assignRole($role);
    }
}