<?php

namespace Tests\Feature;

use Tests\TestCase;

class SwaggerAccessTest extends TestCase
{
    public function test_swagger_is_available_when_enabled(): void
    {
        config(['l5-swagger.enabled' => true]);

        $this->get('/api/documentation')->assertOk();
        $this->get('/docs?api-docs.json')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json');
    }

    public function test_swagger_uses_forwarded_https_urls_behind_railway_proxy(): void
    {
        config([
            'l5-swagger.enabled' => true,
            'l5-swagger.documentations.default.paths.use_absolute_path' => true,
        ]);

        $response = $this
            ->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
            ->withHeaders([
                'X-Forwarded-Host' => 'api.example.test',
                'X-Forwarded-Port' => '443',
                'X-Forwarded-Proto' => 'https',
            ])
            ->get('http://internal/api/documentation');

        $response
            ->assertOk()
            ->assertSee('https://api.example.test/docs?api-docs.json', false)
            ->assertSee('https://api.example.test/docs/asset/swagger-ui.css', false);
    }

    public function test_swagger_returns_not_found_when_disabled(): void
    {
        config(['l5-swagger.enabled' => false]);

        $this->get('/api/documentation')->assertNotFound();
        $this->get('/docs')->assertNotFound();
        $this->get('/api/oauth2-callback')->assertNotFound();
    }
}
