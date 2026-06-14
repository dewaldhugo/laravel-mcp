<?php

namespace OriginMain\LaravelMcp\Tests;

use OriginMain\LaravelMcp\Services\Tools\RunSafeArtisan;

class McpSafeArtisanTest extends TestCase
{
    public function test_security_boundary_violently_rejects_non_whitelisted_commands(): void
    {
        $tool = new RunSafeArtisan();
        $result = $tool->execute(['command' => 'migrate:fresh']);

        $this->assertArrayHasKey('isError', $result);
        $this->assertTrue($result['isError']);
        $this->assertStringContainsString('Security Exception', $result['content'][0]['text']);
    }

    public function test_security_boundary_allows_whitelisted_commands(): void
    {
        $tool = new RunSafeArtisan();
        $result = $tool->execute(['command' => 'about']);

        $this->assertArrayNotHasKey('isError', $result);
        $this->assertStringContainsString('Exit Code', $result['content'][0]['text']);
    }
}
