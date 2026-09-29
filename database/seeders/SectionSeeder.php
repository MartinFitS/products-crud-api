<?php

namespace Database\Seeders;

use App\Models\Section;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    public function run(): void
    {
        $sections = [
            ['code' => 'SEC-000001', 'name' => 'Productos', 'slug' => 'products'],
            ['code' => 'SEC-000002', 'name' => 'Usuarios', 'slug' => 'users'],
            ['code' => 'SEC-000003', 'name' => 'Perfiles', 'slug' => 'profiles'],
        ];

        foreach ($sections as $section) {
            Section::updateOrCreate(
                ['slug' => $section['slug']],
                [...$section, 'is_system' => true],
            );
        }
    }
}
