<?php

namespace Tests\Unit;

use App\Exports\ProductsExport;
use App\Exports\ProfilesExport;
use App\Exports\UsersExport;
use App\Models\Product;
use App\Models\Profile;
use App\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class ExportDateFormatTest extends TestCase
{
    public function test_user_excel_export_uses_the_required_date_format(): void
    {
        $user = new User([
            'code' => 'USR-000001',
            'email' => 'user@example.test',
            'name' => 'Usuario',
        ]);
        $user->created_at = Carbon::parse('2026-09-28 14:35:59');

        $row = (new UsersExport(collect()))->map($user);

        $this->assertSame('28/09/2026 14:35', $row[3]);
    }

    public function test_profile_excel_export_uses_the_required_date_format(): void
    {
        $profile = new Profile([
            'code' => 'PRF-000001',
            'name' => 'Administrador',
            'sections' => ['products'],
        ]);
        $profile->created_at = Carbon::parse('2026-09-28 14:35:59');

        $row = (new ProfilesExport(collect()))->map($profile);

        $this->assertSame('28/09/2026 14:35', $row[3]);
    }

    public function test_product_excel_export_uses_the_required_date_format(): void
    {
        $product = new Product([
            'code' => 'PRD-000001',
            'name' => 'Teclado',
            'brand' => 'Logitech',
            'price' => 899.99,
        ]);
        $product->created_at = Carbon::parse('2026-09-28 14:35:59');

        $row = (new ProductsExport(collect()))->map($product);

        $this->assertSame('28/09/2026 14:35', $row[4]);
    }
}
