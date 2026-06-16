<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'internal_systems.tokens' => ['nexOS' => 'secret-token'],
            'services.contifico.base_url' => 'https://api.contifico.test',
            'services.contifico.timeout' => 30,
        ]);

        RateLimiter::clear('api:*');
    }

    public function test_61st_request_returns_429(): void
    {
        Http::fake([
            'api.contifico.test/*' => Http::response([], 200),
        ]);

        $lastStatus = null;
        for ($i = 0; $i < 60; $i++) {
            $response = $this->authorized()->getJson('/api/v1/contacts?provider=contifico');
            $lastStatus = $response->status();
        }
        $this->assertSame(200, $lastStatus, "60th request should still succeed (got {$lastStatus}).");

        $response = $this->authorized()->getJson('/api/v1/contacts?provider=contifico');

        $response->assertStatus(429)
            ->assertJsonPath('code', 'rate_limited');
    }

    public function test_docs_endpoints_are_not_rate_limited(): void
    {
        for ($i = 0; $i < 80; $i++) {
            $response = $this->get('/api/docs/openapi.yaml');
            $this->assertNotSame(429, $response->status(), "Iteration {$i} got 429");
        }
    }

    protected function authorized(): self
    {
        return $this->withHeaders([
            'Authorization' => 'Bearer secret-token',
            'Accept' => 'application/json',
            'X-Provider-Api-Key' => 'k',
        ]);
    }
}
