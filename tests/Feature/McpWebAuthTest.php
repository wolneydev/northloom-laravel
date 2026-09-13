<?php

namespace Tests\Feature;

use Tests\TestCase;

class McpWebAuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['services.mcp.web_token' => 'test-mcp-token']);
    }

    private function initializePayload(): array
    {
        return [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2025-03-26',
                'capabilities' => [],
                'clientInfo' => ['name' => 'test-client', 'version' => '1.0'],
            ],
        ];
    }

    public function test_web_mcp_server_rejects_requests_without_a_token(): void
    {
        $response = $this->postJson('/mcp/hospitable', $this->initializePayload());

        $response->assertStatus(401);
    }

    public function test_web_mcp_server_rejects_requests_with_an_invalid_token(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer wrong-token')
            ->postJson('/mcp/hospitable', $this->initializePayload());

        $response->assertStatus(401);
    }

    public function test_web_mcp_server_accepts_requests_with_a_valid_token(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer test-mcp-token')
            ->postJson('/mcp/hospitable', $this->initializePayload());

        $response->assertOk();
        $response->assertJsonPath('result.serverInfo.name', 'Hospitable');
    }
}
