<?php

namespace DewaldHugo\LaravelMcp\Tests;

use DewaldHugo\LaravelMcp\Commands\McpServeCommand;

class McpHandshakeTest extends TestCase
{
    public function test_server_successfully_completes_spec_compliant_handshake(): void
    {
        $inputStream = fopen('php://memory', 'r+');
        $outputStream = fopen('php://memory', 'r+');

        fwrite($inputStream, json_encode([
            'jsonrpc' => '2.0', 'id' => 101, 'method' => 'initialize',
            'params' => ['protocolVersion' => '2025-11-25', 'capabilities' => (object)[], 'clientInfo' => ['name' => 'test-client', 'version' => '1.0']]
        ]) . "\n");

        rewind($inputStream);

        // Bind the streams into the container so the command resolves them
        $this->app->instance('mcp.input', $inputStream);
        $this->app->instance('mcp.output', $outputStream);

        $this->artisan('mcp:serve');

        rewind($outputStream);
        $outputContent = stream_get_contents($outputStream);
        
        $this->assertNotEmpty($outputContent);
        $responseFrame = json_decode(trim($outputContent), true);
        
        $this->assertEquals('2.0', $responseFrame['jsonrpc']);
        $this->assertEquals(101, $responseFrame['id']);
        $this->assertEquals('2025-11-25', $responseFrame['result']['protocolVersion']);

        fclose($inputStream);
        fclose($outputStream);
    }
}