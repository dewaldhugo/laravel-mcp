<?php

namespace DewaldHugo\LaravelMcp\Tests;

use DewaldHugo\LaravelMcp\Services\Tools\ListRoutes;
use Illuminate\Support\Facades\Cache;

class McpPerformanceTest extends TestCase
{
    public function test_router_tool_memoizes_and_caches_state_correctly(): void
    {
        Cache::flush();
        $tool = new ListRoutes();

        $firstPass = $tool->execute([]);
        $secondPass = $tool->execute([]);

        $this->assertEquals($firstPass, $secondPass);
        $this->assertArrayHasKey('content', $firstPass);
        $this->assertStringContainsString('uri', $firstPass['content'][0]['text']);
    }
}