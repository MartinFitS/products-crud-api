<?php

namespace Tests\Feature\Seeder;

use App\Models\Profile;
use App\Models\Section;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    private const ADMIN_EMAIL = 'bootstrap-admin@example.test';

    protected function setUp(): void
    {
        parent::setUp();

        User::where('email', self::ADMIN_EMAIL)->delete();
        User::where('code', 'USR-000001')->delete();
    }

    protected function tearDown(): void
    {
        User::where('email', self::ADMIN_EMAIL)->delete();

        parent::tearDown();
    }

    public function test_database_seeder_is_idempotent_and_does_not_reset_admin_password(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', self::ADMIN_EMAIL)->firstOrFail();
        $this->assertTrue(Hash::check('BootstrapAdmin123!', $admin->password));
        $this->assertSame(4, Section::whereIn('slug', [
            'products',
            'users',
            'profiles',
            'audit-logs',
        ])->count());
        $this->assertSame(3, Profile::whereIn('code', [
            'PRF-000001',
            'PRF-000002',
            'PRF-000003',
        ])->count());

        $admin->password = 'PasswordChanged123!';
        $admin->profile_ids = [];
        $admin->save();

        $this->seed(DatabaseSeeder::class);

        $admin->refresh();
        $adminProfile = Profile::where('code', 'PRF-000001')->firstOrFail();

        $this->assertSame(1, User::where('email', self::ADMIN_EMAIL)->count());
        $this->assertTrue(Hash::check('PasswordChanged123!', $admin->password));
        $this->assertContains(
            (string) $adminProfile->getKey(),
            collect($admin->profile_ids)->map(fn ($id): string => (string) $id)->all()
        );
    }

    public function test_admin_seeder_fails_when_bootstrap_credentials_are_missing(): void
    {
        config()->set('bootstrap.admin.email');
        config()->set('bootstrap.admin.password');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('BOOTSTRAP_ADMIN_EMAIL');

        $this->seed(AdminUserSeeder::class);
    }
}
