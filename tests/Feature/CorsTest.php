<?php

namespace Tests\Feature;

use Tests\TestCase;

class CorsTest extends TestCase
{
    public function test_api_accepts_preflight_requests_from_frontend_origin(): void
    {
        config(['cors.allowed_origins' => ['http://localhost:5173']]);

        $response = $this
            ->withHeaders([
                'Origin' => 'http://localhost:5173',
                'Access-Control-Request-Method' => 'POST',
            ])
            ->options('/api/pokemons/battle');

        $response->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
    }
}
