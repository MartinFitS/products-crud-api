<?php

namespace Tests\Feature\AuditLog;

use App\Models\AuditLog;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    private User $actor;

    private Profile $profile;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $marker = (string) Str::uuid();
        $this->profile = Profile::create(['code' => 'TEST-AUD-'.Str::upper(Str::random(8)), 'name' => 'Audit '.$marker, 'sections' => ['audit-logs']]);
        $this->actor = User::create(['code' => 'TEST-ACTOR-'.Str::upper(Str::random(8)), 'name' => 'Audit Actor '.$marker, 'email' => "audit-{$marker}@example.test", 'password' => 'ActorPassword123!', 'profile_ids' => [$this->profile->_id], 'is_active' => true]);
        $this->token = $this->actor->createToken('audit-test')->plainTextToken;
    }

    protected function tearDown(): void
    {
        AuditLog::where('user_id', $this->actor->_id)->delete();
        $this->actor->tokens()->delete();
        $this->actor->delete();
        $this->profile->delete();
        parent::tearDown();
    }

    public function test_list_supports_filters_search_and_detail(): void
    {
        $entry = AuditLog::create(['user_id' => $this->actor->_id, 'action' => 'updated', 'auditable_type' => 'product', 'auditable_id' => $this->actor->_id, 'old_values' => ['name' => 'Anterior'], 'new_values' => ['name' => 'Producto auditable']]);

        $this->withToken($this->token)->getJson('/api/audit-logs?search=auditable&action=updated&auditable_type=product')
            ->assertOk()->assertJsonPath('data.0.id', (string) $entry->getKey())->assertJsonPath('data.0.actor.email', $this->actor->email)->assertJsonPath('meta.total', 1);
        $this->withToken($this->token)->getJson('/api/audit-logs/'.$entry->getKey())
            ->assertOk()->assertJsonPath('data.old_values.name', 'Anterior')->assertJsonPath('data.new_values.name', 'Producto auditable');
    }

    public function test_routes_require_authentication_and_section(): void
    {
        $this->getJson('/api/audit-logs')->assertUnauthorized();
        $this->profile->update(['sections' => ['products']]);
        $this->withToken($this->token)->getJson('/api/audit-logs')->assertForbidden();
    }

    public function test_exports_return_downloads(): void
    {
        $this->withToken($this->token)->get('/api/audit-logs/export/pdf')->assertOk()->assertDownload('audit-logs-'.now()->format('Y-m-d').'.pdf');
        $this->withToken($this->token)->get('/api/audit-logs/export/excel')->assertOk()->assertDownload('audit-logs-'.now()->format('Y-m-d').'.xlsx');
    }
}
