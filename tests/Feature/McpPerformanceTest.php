<?php

namespace DewaldHugo\LaravelMcp\Tests;

use DewaldHugo\LaravelMcp\Services\Tools\ListRoutes;

class McpPerformanceTest extends TestCase
{
    public function test_router_tool_memoizes_and_caches_state_correctly(): void
    {
        $tool = new ListRoutes();

        // First execution: compiles the raw state
        $firstPass = $tool->execute([]);
        
        // Second execution: must hit the memoization cache gate instantly
        $secondPass = $tool->execute([]);

        $this->assertEquals($firstPass, $secondPass);
        $this->assertStringContainsString('uri', $firstPass['content'][0]['text']);
    }
}
