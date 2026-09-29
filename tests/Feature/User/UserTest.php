<?php

namespace Tests\Feature\User;

use App\Models\AuditLog;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use Tests\TestCase;

class UserTest extends TestCase
{
    private User $actor;

    private Profile $profile;

    private string $marker;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->marker = (string) Str::uuid();
        $this->profile = Profile::create([
            'code' => 'TEST-PRF-'.Str::upper(Str::random(8)),
            'name' => 'Test Profile',
            'sections' => ['users'],
        ]);
        $this->actor = User::create([
            'code' => 'TEST-ACTOR-'.Str::upper(Str::random(8)),
            'name' => 'Users Test Actor',
            'email' => "actor-{$this->marker}@example.test",
            'phone' => '+521234567890',
            'photo' => null,
            'password' => 'ActorPassword123!',
            'profile_ids' => [$this->profile->_id],
            'is_active' => true,
        ]);
        $this->token = $this->actor->createToken('users-test')->plainTextToken;
    }

    protected function tearDown(): void
    {
        AuditLog::where('user_id', $this->actor->_id)->delete();
        $users = User::where('email', 'regex', new Regex(preg_quote($this->marker), 'i'))->get();

        foreach ($users as $user) {
            $user->tokens()->delete();

            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }

            $user->delete();
        }

        $this->profile->delete();

        parent::tearDown();
    }

    public function test_user_list_requires_authentication(): void
    {
        $this->getJson('/api/users')->assertUnauthorized();
    }

    public function test_user_without_profiles_section_cannot_list_profiles(): void
    {
        $this->withToken($this->token)
            ->getJson('/api/profiles')
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_authenticated_user_can_create_a_user_with_native_profile_ids(): void
    {
        $response = $this->withToken($this->token)->post('/api/users', $this->validPayload(), [
            'Accept' => 'application/json',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', "new-{$this->marker}@example.test")
            ->assertJsonPath('data.is_active', true)
            ->assertJsonMissingPath('data.password');

        $user = User::where('email', "new-{$this->marker}@example.test")->firstOrFail();

        $this->assertMatchesRegularExpression('/^USR-\d{6,}$/', $user->code);
        $this->assertInstanceOf(ObjectId::class, $user->profile_ids[0]);
        $this->assertTrue(Storage::disk('public')->exists($user->photo));

        $audit = AuditLog::where('auditable_id', $user->_id)->firstOrFail();
        $this->assertSame('created', $audit->action);
        $this->assertArrayNotHasKey('password', $audit->new_values);
    }

    public function test_create_rejects_a_duplicate_email(): void
    {
        $payload = $this->validPayload(['email' => $this->actor->email]);

        $this->withToken($this->token)
            ->post('/api/users', $payload, ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_create_accepts_the_scalar_profile_id_sent_by_swagger(): void
    {
        $payload = $this->validPayload([
            'profile_ids' => (string) $this->profile->getKey(),
        ]);

        $this->withToken($this->token)
            ->post('/api/users', $payload, ['Accept' => '*/*'])
            ->assertCreated()
            ->assertJsonPath('data.profile_ids.0', (string) $this->profile->getKey());
    }

    public function test_create_rejects_a_nonexistent_profile(): void
    {
        $payload = $this->validPayload([
            'profile_ids' => [(string) new ObjectId],
        ]);

        $this->withToken($this->token)
            ->post('/api/users', $payload, ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('profile_ids.0');
    }

    public function test_user_list_supports_search_status_and_pagination(): void
    {
        $target = $this->createTargetUser(['name' => 'Searchable Martin']);

        $this->withToken($this->token)
            ->getJson('/api/users?page=1&limit=10&search=martin&is_active=true')
            ->assertOk()
            ->assertJsonPath('data.0.id', (string) $target->getKey())
            ->assertJsonPath('meta.page', 1)
            ->assertJsonPath('meta.limit', 10)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonMissingPath('data.0.password');
    }

    public function test_user_detail_includes_resolved_profiles(): void
    {
        $target = $this->createTargetUser();

        $this->withToken($this->token)
            ->getJson('/api/users/'.$target->getKey())
            ->assertOk()
            ->assertJsonPath('data.email', $target->email)
            ->assertJsonPath('data.profiles.0.id', (string) $this->profile->getKey())
            ->assertJsonPath('data.profiles.0.sections.0', 'users')
            ->assertJsonMissingPath('data.password');
    }

    public function test_user_can_be_updated_without_resending_password(): void
    {
        $target = $this->createTargetUser();

        $this->withToken($this->token)
            ->postJson('/api/users/'.$target->getKey(), [
                'name' => 'Updated Name',
                'email' => $target->email,
                'phone' => '+523141234567',
                'profile_ids' => [(string) $this->profile->getKey()],
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.phone', '+523141234567');

        $audit = AuditLog::where('auditable_id', $target->_id)->firstOrFail();
        $this->assertSame('updated', $audit->action);
        $this->assertSame('Target User', $audit->old_values['name']);
        $this->assertSame('Updated Name', $audit->new_values['name']);
        $this->assertArrayNotHasKey('password', $audit->old_values);
        $this->assertArrayNotHasKey('password', $audit->new_values);
    }

    public function test_updating_the_photo_removes_the_previous_file(): void
    {
        $oldPath = 'users/TEST/old-photo.png';
        Storage::disk('public')->put($oldPath, 'old-image');
        $target = $this->createTargetUser(['photo' => $oldPath]);

        $response = $this->withToken($this->token)->post('/api/users/'.$target->getKey(), [
            'photo' => UploadedFile::fake()->image('new-photo.webp', 300, 300),
        ], ['Accept' => 'application/json']);

        $response->assertOk();

        $newPath = $response->json('data.photo');
        $this->assertNotSame($oldPath, $newPath);
        $this->assertFalse(Storage::disk('public')->exists($oldPath));
        $this->assertTrue(Storage::disk('public')->exists($newPath));
    }

    public function test_multipart_user_update_works_through_the_post_compatibility_endpoint(): void
    {
        $target = $this->createTargetUser();

        $response = $this->withToken($this->token)->post('/api/users/'.$target->getKey(), [
            'name' => 'Updated From Swagger',
            'profile_ids' => (string) $this->profile->getKey(),
            'photo' => UploadedFile::fake()->image('swagger-photo.png', 300, 300),
        ], ['Accept' => '*/*']);

        $response
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated From Swagger')
            ->assertJsonPath('data.profile_ids.0', (string) $this->profile->getKey());

        $target->refresh();
        $this->assertSame('Updated From Swagger', $target->name);
        $this->assertTrue(Storage::disk('public')->exists($target->photo));
    }

    public function test_empty_optional_values_sent_by_swagger_keep_existing_values(): void
    {
        $target = $this->createTargetUser();

        $this->withToken($this->token)
            ->post('/api/users/'.$target->getKey(), [
                'password' => '',
                'password_confirmation' => '',
                'profile_ids' => '',
            ], ['Accept' => '*/*'])
            ->assertOk()
            ->assertJsonPath('data.name', $target->name)
            ->assertJsonPath('data.email', $target->email)
            ->assertJsonPath('data.profile_ids.0', (string) $this->profile->getKey());
    }

    public function test_disabling_a_user_revokes_all_tokens(): void
    {
        $target = $this->createTargetUser();
        $target->createToken('session-one');
        $target->createToken('session-two');

        $this->withToken($this->token)
            ->patchJson('/api/users/'.$target->getKey().'/status', ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->assertSame(0, $target->tokens()->count());
        $this->assertSame(
            'status_updated',
            AuditLog::where('auditable_id', $target->_id)->firstOrFail()->action,
        );
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $target = $this->createTargetUser(['is_active' => false]);

        $this->postJson('/api/auth/login', [
            'email' => $target->email,
            'password' => 'TargetPassword123!',
        ])
            ->assertForbidden()
            ->assertJsonPath('message', 'El usuario se encuentra inactivo.');
    }

    public function test_deleting_a_user_removes_photo_and_tokens(): void
    {
        $path = 'users/TEST/photo.png';
        Storage::disk('public')->put($path, 'image');
        $target = $this->createTargetUser(['photo' => $path]);
        $targetId = $target->_id;
        $target->createToken('target-session');

        $this->withToken($this->token)
            ->deleteJson('/api/users/'.$target->getKey())
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNull(User::find($target->getKey()));
        $this->assertFalse(Storage::disk('public')->exists($path));
        $audit = AuditLog::where('auditable_id', $targetId)->firstOrFail();
        $this->assertSame('deleted', $audit->action);
        $this->assertNull($audit->new_values);
    }

    public function test_pdf_export_returns_a_download(): void
    {
        $this->withToken($this->token)
            ->get('/api/users/export/pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload('users-'.now()->format('Y-m-d').'.pdf');
    }

    public function test_excel_export_returns_a_download(): void
    {
        $this->withToken($this->token)
            ->get('/api/users/export/excel')
            ->assertOk()
            ->assertDownload('users-'.now()->format('Y-m-d').'.xlsx');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'New Test User',
            'email' => "new-{$this->marker}@example.test",
            'phone' => '+523141234567',
            'password' => 'NewUserPassword123!',
            'password_confirmation' => 'NewUserPassword123!',
            'profile_ids' => [(string) $this->profile->getKey()],
            'photo' => UploadedFile::fake()->image('profile.png', 300, 300),
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createTargetUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'code' => 'TEST-USR-'.Str::upper(Str::random(8)),
            'name' => 'Target User',
            'email' => 'target-'.Str::random(6)."-{$this->marker}@example.test",
            'phone' => null,
            'photo' => null,
            'password' => 'TargetPassword123!',
            'profile_ids' => [$this->profile->_id],
            'is_active' => true,
        ], $overrides));
    }
}
