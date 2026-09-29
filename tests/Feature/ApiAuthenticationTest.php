<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    public function test_protected_api_returns_401_when_accept_header_is_not_json(): void
    {
        $this->post('/api/users', [], ['Accept' => '*/*'])
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'No autenticado.');
    }

    public function test_profile_list_requires_authentication(): void
    {
        $this->getJson('/api/profiles')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'No autenticado.');
    }
}
