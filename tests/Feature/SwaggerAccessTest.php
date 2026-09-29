<?php

namespace Tests\Feature;

use Tests\TestCase;

class SwaggerAccessTest extends TestCase
{
    public function test_swagger_is_available_when_enabled(): void
    {
        config(['l5-swagger.enabled' => true]);

        $this->get('/api/documentation')->assertOk();
    }

    public function test_swagger_returns_not_found_when_disabled(): void
    {
        config(['l5-swagger.enabled' => false]);

        $this->get('/api/documentation')->assertNotFound();
        $this->get('/docs')->assertNotFound();
        $this->get('/api/oauth2-callback')->assertNotFound();
    }
}
