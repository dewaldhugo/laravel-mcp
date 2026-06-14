<?php

namespace DewaldHugo\LaravelMcp\Tests;

use DewaldHugo\LaravelMcp\Services\Tools\ReadModelSchema;

class McpModelSchemaTest extends TestCase
{
    public function test_schema_tool_identifies_invalid_model_classes(): void
    {
        $tool = new ReadModelSchema();
        $result = $tool->execute(['model' => 'NonExistent\ClassPath']);

        $this->assertTrue($result['isError']);
        $this->assertStringContainsString("does not exist", $result['content'][0]['text']);
    }
}