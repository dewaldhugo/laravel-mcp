<?php

namespace DewaldHugo\LaravelMcp\Tests;

use DewaldHugo\LaravelMcp\Commands\McpServeCommand;

class McpHandshakeTest extends TestCase
{
    public function test_server_successfully_completes_spec_compliant_handshake(): void
    {
        // 1. Create in-memory stream handles
        $inputStream = fopen('php://memory', 'r+');
        $outputStream = fopen('php://memory', 'r+');
        $errorStream = fopen('php://memory', 'r+');

        // 2. Feed standard MCP 2025-11-25 Initialization payloads into input stream
        fwrite($inputStream, json_encode([
            'jsonrpc' => '2.0',
            'id' => 101,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2025-11-25',
                'capabilities' => (object)[],
                'clientInfo' => ['name' => 'test-client', 'version' => '1.0']
            ]
        ]) . "\n");

        fwrite($inputStream, json_encode([
            'jsonrpc' => '2.0',
            'method' => 'notifications/initialized'
        ]) . "\n");

        // Rewind input stream pointer so the command loop can read it from the top
        rewind($inputStream);

        /** @var McpServeCommand $command */
        $command = app(McpServeCommand::class);
        $command->inputStream = $inputStream;
        $command->outputStream = $outputStream;
        $command->errorStream = $errorStream;

        // 3. Execute the command loop
        $this->artisan('mcp:serve');

        // 4. Assert and parse outputs
        rewind($outputStream);
        $outputContent = stream_get_contents($outputStream);
        
        $this->assertNotEmpty($outputContent);
        
        $responseFrame = json_decode(trim($outputContent), true);
        
        $this->assertEquals('2.0', $responseFrame['jsonrpc']);
        $this->assertEquals(101, $responseFrame['id']);
        $this->assertEquals('2025-11-25', $responseFrame['result']['protocolVersion']);
        $this->assertEquals('origin-main/laravel-mcp', $responseFrame['result']['serverInfo']['name']);

        // Clean up open handles
        fclose($inputStream);
        fclose($outputStream);
        fclose($errorStream);
    }
}
