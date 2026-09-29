<?php

namespace Database\Seeders;

use App\Models\Profile;
use Illuminate\Database\Seeder;

class ProfileSeeder extends Seeder
{
    public function run(): void
    {
        $profiles = [
            [
                'code' => 'PRF-000001',
                'name' => 'Administrador',
                'sections' => ['products', 'users', 'profiles', 'audit-logs'],
            ],
            [
                'code' => 'PRF-000002',
                'name' => 'Operador de productos',
                'sections' => ['products'],
            ],
            [
                'code' => 'PRF-000003',
                'name' => 'Gestor de usuarios',
                'sections' => ['users'],
            ],
        ];

        foreach ($profiles as $profile) {
            Profile::updateOrCreate(
                ['code' => $profile['code']],
                $profile,
            );
        }
    }
}
