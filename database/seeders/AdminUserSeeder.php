<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $credentials = [
            'name' => trim((string) config('bootstrap.admin.name')),
            'email' => strtolower(trim((string) config('bootstrap.admin.email'))),
            'password' => (string) config('bootstrap.admin.password'),
        ];

        $validator = Validator::make($credentials, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => [
                'required',
                Password::min(8)->mixedCase()->letters()->numbers()->symbols(),
            ],
        ]);

        if ($validator->fails()) {
            throw new RuntimeException(
                'Configura BOOTSTRAP_ADMIN_NAME, BOOTSTRAP_ADMIN_EMAIL y '
                .'BOOTSTRAP_ADMIN_PASSWORD antes de ejecutar los seeds. '
                .implode(' ', $validator->errors()->all())
            );
        }

        $adminProfile = Profile::where(
            'code',
            'PRF-000001'
        )->firstOrFail();

        $user = User::where('email', $credentials['email'])->first();

        if ($user === null) {
            $codeOwner = User::where('code', 'USR-000001')->first();

            if ($codeOwner !== null) {
                throw new RuntimeException(
                    'No se puede crear el administrador inicial: el código '
                    .'USR-000001 ya pertenece a otro usuario.'
                );
            }

            User::create([
                'code' => 'USR-000001',
                'name' => $credentials['name'],
                'email' => $credentials['email'],
                'phone' => null,
                'photo' => null,
                'password' => $credentials['password'],
                'profile_ids' => [
                    $adminProfile->_id,
                ],
                'is_active' => true,
            ]);

            return;
        }

        $profileIds = $user->profile_ids ?? [];
        $hasAdminProfile = collect($profileIds)->contains(
            fn ($profileId): bool => (string) $profileId === (string) $adminProfile->getKey()
        );

        if (! $hasAdminProfile) {
            $profileIds[] = $adminProfile->_id;
            $user->profile_ids = $profileIds;
            $user->save();
        }
    }
}
