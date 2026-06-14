<?php

namespace DewaldHugo\LaravelMcp\Tests;

use DewaldHugo\LaravelMcp\Services\Tools\RunSafeArtisan;

class McpSafeArtisanTest extends TestCase
{
    public function test_security_boundary_violently_rejects_non_whitelisted_commands(): void
    {
        $tool = new RunSafeArtisan();
        $result = $tool->execute(['command' => 'migrate:fresh']);
        $this->assertTrue($result['isError']);
    }

    public function test_security_boundary_allows_whitelisted_commands(): void
    {
        $tool = new RunSafeArtisan();
        $result = $tool->execute(['command' => 'about']);
        $this->assertArrayNotHasKey('isError', $result);
    }
}