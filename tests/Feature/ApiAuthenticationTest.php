<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['internal_systems.tokens' => ['nexOS' => 'secret-token']]);
    }

    public function test_it_requires_a_bearer_token(): void
    {
        $response = $this->getJson('/api/v1/contacts?provider=contifico');

        $response->assertStatus(401)
            ->assertJsonPath('code', 'invalid_internal_token');
    }

    public function test_it_rejects_invalid_bearer_tokens(): void
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer wrong-token',
        ])->getJson('/api/v1/contacts?provider=contifico');

        $response->assertStatus(401)
            ->assertJsonPath('code', 'invalid_internal_token');
    }
}
