<?php

namespace Tests\Feature;

use Tests\TestCase;

class SpaShellTest extends TestCase
{
    public function test_spa_shell_renders_successfully(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('id="app"', false);
        $response->assertSee('AmarSocialContent', false);
    }

    public function test_spa_catches_deep_routes_for_client_router(): void
    {
        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('id="app"', false);
    }
}

