<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_railway_healthcheck_is_available(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_api_health_endpoint_is_available(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
