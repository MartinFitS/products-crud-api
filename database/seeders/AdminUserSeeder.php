<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminProfile = Profile::where(
            'code',
            'PRF-000001'
        )->firstOrFail();

        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'code' => 'USR-000001',
                'name' => 'Administrador',
                'phone' => null,
                'photo' => null,
                'password' => 'Admin123!',
                'profile_ids' => [
                    $adminProfile->_id,
                ],
                'is_active' => true,
            ],
        );
    }
}
