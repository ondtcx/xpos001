<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'admin@xpos.local'],
            [
                'name' => 'Administrador',
                'username' => 'admin',
                'password' => 'admin12345',
                'is_active' => true,
            ]
        );

        $adminRole = Role::query()->where('name', 'admin')->first();

        if ($adminRole !== null) {
            $user->roles()->syncWithoutDetaching([$adminRole->id]);
        }
    }
}
