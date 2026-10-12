<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Llamamos al seeder de permisos y roles
        $this->call([
            PermissionsDemoSeeder::class,
        ]);
    }
}
