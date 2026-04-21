<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::query()->updateOrCreate(
            ['name' => 'admin'],
            ['description' => 'Administrador del sistema']
        );

        Role::query()->updateOrCreate(
            ['name' => 'assistant'],
            ['description' => 'Ayudante o cajero con permisos limitados']
        );
    }
}
