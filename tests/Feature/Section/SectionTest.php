<?php

namespace Tests\Feature\Section;

use App\Models\Profile;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Str;
use MongoDB\BSON\Regex;
use Tests\TestCase;

class SectionTest extends TestCase
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
            'name' => 'Sections Access '.$this->marker,
            'sections' => ['profiles'],
        ]);
        $this->actor = User::create([
            'code' => 'TEST-ACTOR-'.Str::upper(Str::random(8)),
            'name' => 'Sections Test Actor',
            'email' => "sections-{$this->marker}@example.test",
            'phone' => null,
            'photo' => null,
            'password' => 'ActorPassword123!',
            'profile_ids' => [$this->accessProfile->_id],
            'is_active' => true,
        ]);
        $this->token = $this->actor->createToken('sections-test')->plainTextToken;
    }

    protected function tearDown(): void
    {
        $this->actor->tokens()->delete();
        $this->actor->delete();
        $this->accessProfile->delete();
        Section::where('name', 'regex', new Regex(preg_quote($this->marker), 'i'))->delete();

        parent::tearDown();
    }

    public function test_authorized_user_can_list_all_sections(): void
    {
        $section = $this->createSection('Listado', 'listado');

        $this->withToken($this->token)
            ->getJson('/api/sections')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment([
                'id' => (string) $section->getKey(),
                'code' => $section->code,
                'name' => $section->name,
                'slug' => $section->slug,
                'is_system' => false,
            ]);
    }

    public function test_authorized_user_can_create_a_section_with_generated_code_and_slug(): void
    {
        $response = $this->withToken($this->token)->postJson('/api/sections', [
            'name' => 'Reportes '.$this->marker,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Reportes '.$this->marker)
            ->assertJsonPath('data.is_system', false);

        $this->assertMatchesRegularExpression('/^SEC-\d{6,}$/', $response->json('data.code'));
        $this->assertStringStartsWith('reportes-', $response->json('data.slug'));
    }

    public function test_section_name_and_slug_must_be_unique(): void
    {
        $section = $this->createSection('Única', 'unica');

        $this->withToken($this->token)
            ->postJson('/api/sections', [
                'name' => $section->name,
                'slug' => 'otro-slug',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->withToken($this->token)
            ->postJson('/api/sections', [
                'name' => 'Otro nombre '.$this->marker,
                'slug' => $section->slug,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('slug');
    }

    public function test_authorized_user_can_delete_an_unassigned_custom_section(): void
    {
        $section = $this->createSection('Eliminable', 'eliminable');

        $this->withToken($this->token)
            ->deleteJson('/api/sections/'.$section->getKey())
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNull(Section::find($section->getKey()));
    }

    public function test_system_or_assigned_sections_cannot_be_deleted(): void
    {
        $system = $this->createSection('Sistema', 'sistema', true);
        $assigned = $this->createSection('Asignada', 'asignada');
        $profile = Profile::create([
            'code' => 'TEST-PRF-'.Str::upper(Str::random(8)),
            'name' => 'Assigned Section '.$this->marker,
            'sections' => [$assigned->slug],
        ]);

        try {
            $this->withToken($this->token)
                ->deleteJson('/api/sections/'.$system->getKey())
                ->assertUnprocessable()
                ->assertJsonValidationErrors('section');

            $this->withToken($this->token)
                ->deleteJson('/api/sections/'.$assigned->getKey())
                ->assertUnprocessable()
                ->assertJsonValidationErrors('section');
        } finally {
            $profile->delete();
        }
    }

    public function test_user_without_profiles_access_cannot_manage_sections(): void
    {
        $profile = Profile::create([
            'code' => 'TEST-PRF-'.Str::upper(Str::random(8)),
            'name' => 'Products Section '.$this->marker,
            'sections' => ['products'],
        ]);
        $user = User::create([
            'code' => 'TEST-USR-'.Str::upper(Str::random(8)),
            'name' => 'No Sections Access',
            'email' => "no-sections-{$this->marker}@example.test",
            'password' => 'Password123!',
            'profile_ids' => [$profile->_id],
            'is_active' => true,
        ]);
        $token = $user->createToken('no-sections')->plainTextToken;

        try {
            $this->withToken($token)->getJson('/api/sections')->assertForbidden();
        } finally {
            $user->tokens()->delete();
            $user->delete();
            $profile->delete();
        }
    }

    private function createSection(string $name, string $slug, bool $isSystem = false): Section
    {
        return Section::create([
            'code' => 'TEST-SEC-'.Str::upper(Str::random(8)),
            'name' => $name.' '.$this->marker,
            'slug' => $slug.'-'.Str::lower(Str::random(6)),
            'is_system' => $isSystem,
        ]);
    }
}
