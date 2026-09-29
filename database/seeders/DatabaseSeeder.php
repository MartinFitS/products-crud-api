<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SectionSeeder::class,
            ProfileSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
