<?php

namespace Tests\Feature\Profile;

use App\Models\AuditLog;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Str;
use MongoDB\BSON\Regex;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    private User $actor;

    private Profile $accessProfile;

    private string $marker;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->marker = (string) Str::uuid();
        $this->accessProfile = Profile::create([
            'code' => 'TEST-PRF-'.Str::upper(Str::random(8)),
            'name' => 'Profiles Access '.$this->marker,
            'sections' => ['profiles'],
        ]);
        $this->actor = User::create([
            'code' => 'TEST-ACTOR-'.Str::upper(Str::random(8)),
            'name' => 'Profiles Test Actor',
            'email' => "profiles-{$this->marker}@example.test",
            'phone' => null,
            'photo' => null,
            'password' => 'ActorPassword123!',
            'profile_ids' => [$this->accessProfile->_id],
            'is_active' => true,
        ]);
        $this->token = $this->actor->createToken('profiles-test')->plainTextToken;
    }

    protected function tearDown(): void
    {
        AuditLog::where('user_id', $this->actor->_id)->delete();
        $this->actor->tokens()->delete();
        $this->actor->delete();

        Profile::where('name', 'regex', new Regex(preg_quote($this->marker), 'i'))->delete();

        parent::tearDown();
    }

    public function test_profile_list_supports_search_and_pagination(): void
    {
        $target = $this->createProfile('Perfil Buscable', ['products']);

        $this->withToken($this->token)
            ->getJson('/api/profiles?page=1&limit=10&search=Buscable')
            ->assertOk()
            ->assertJsonPath('data.0.id', (string) $target->getKey())
            ->assertJsonPath('data.0.sections.0', 'products')
            ->assertJsonPath('meta.page', 1)
            ->assertJsonPath('meta.limit', 10)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_authorized_user_can_create_a_profile_with_an_automatic_code(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/profiles', [
            'name' => 'Nuevo Perfil '.$this->marker,
            'sections' => ['products', 'users'],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Nuevo Perfil '.$this->marker)
            ->assertJsonPath('data.sections', ['products', 'users']);

        $this->assertMatchesRegularExpression('/^PRF-\d{6,}$/', $response->json('data.code'));
        $this->assertNotNull($response->json('data.created_at'));

        $profile = Profile::findOrFail($response->json('data.id'));
        $audit = AuditLog::where('auditable_id', $profile->_id)->firstOrFail();
        $this->assertSame('created', $audit->action);
        $this->assertSame(['products', 'users'], $audit->new_values['sections']);
    }

    public function test_create_rejects_unknown_sections_and_duplicate_names(): void
    {
        $profile = $this->createProfile('Perfil Único', ['products']);

        $this->withToken($this->token)
            ->postJson('/api/profiles', [
                'name' => 'Inválido '.$this->marker,
                'sections' => ['unknown'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sections.0');

        $this->withToken($this->token)
            ->postJson('/api/profiles', [
                'name' => $profile->name,
                'sections' => ['users'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    public function test_authorized_user_can_view_and_update_a_profile(): void
    {
        $profile = $this->createProfile('Perfil Editable', ['products']);

        $this->withToken($this->token)
            ->getJson('/api/profiles/'.$profile->getKey())
            ->assertOk()
            ->assertJsonPath('data.code', $profile->code)
            ->assertJsonPath('data.name', $profile->name);

        $this->withToken($this->token)
            ->putJson('/api/profiles/'.$profile->getKey(), [
                'name' => 'Perfil Actualizado '.$this->marker,
                'sections' => ['products', 'users'],
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Perfil Actualizado '.$this->marker)
            ->assertJsonPath('data.sections', ['products', 'users']);

        $audit = AuditLog::where('auditable_id', $profile->_id)->firstOrFail();
        $this->assertSame('updated', $audit->action);
        $this->assertSame(['products'], $audit->old_values['sections']);
        $this->assertSame(['products', 'users'], $audit->new_values['sections']);
    }

    public function test_authorized_user_can_delete_an_unassigned_profile(): void
    {
        $profile = $this->createProfile('Perfil Eliminable', ['products']);
        $profileId = $profile->_id;

        $this->withToken($this->token)
            ->deleteJson('/api/profiles/'.$profile->getKey())
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNull(Profile::find($profile->getKey()));
        $audit = AuditLog::where('auditable_id', $profileId)->firstOrFail();
        $this->assertSame('deleted', $audit->action);
        $this->assertNull($audit->new_values);
    }

    public function test_assigned_profile_cannot_be_deleted(): void
    {
        $this->withToken($this->token)
            ->deleteJson('/api/profiles/'.$this->accessProfile->getKey())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('profile');
    }

    public function test_profile_exports_return_downloads(): void
    {
        $this->withToken($this->token)
            ->get('/api/profiles/export/pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload('profiles-'.now()->format('Y-m-d').'.pdf');

        $this->withToken($this->token)
            ->get('/api/profiles/export/excel')
            ->assertOk()
            ->assertDownload('profiles-'.now()->format('Y-m-d').'.xlsx');
    }

    public function test_profiles_only_user_cannot_access_users_module(): void
    {
        $this->withToken($this->token)
            ->getJson('/api/users')
            ->assertForbidden()
            ->assertJsonPath('message', 'No tienes autorización para realizar esta acción.');
    }

    public function test_products_only_user_cannot_access_users_or_profiles(): void
    {
        $productProfile = $this->createProfile('Solo Productos', ['products']);
        $productUser = User::create([
            'code' => 'TEST-PRODUCTS-'.Str::upper(Str::random(8)),
            'name' => 'Products Only User',
            'email' => "products-{$this->marker}@example.test",
            'phone' => null,
            'photo' => null,
            'password' => 'ProductPassword123!',
            'profile_ids' => [$productProfile->_id],
            'is_active' => true,
        ]);
        $token = $productUser->createToken('products-only')->plainTextToken;

        try {
            $this->withToken($token)->getJson('/api/users')->assertForbidden();
            $this->withToken($token)->getJson('/api/profiles')->assertForbidden();
        } finally {
            $productUser->tokens()->delete();
            $productUser->delete();
        }
    }

    private function createProfile(string $name, array $sections): Profile
    {
        return Profile::create([
            'code' => 'TEST-PRF-'.Str::upper(Str::random(8)),
            'name' => $name.' '.$this->marker,
            'sections' => $sections,
        ]);
    }
}
