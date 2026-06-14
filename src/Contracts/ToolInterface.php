<?php

namespace DewaldHugo\LaravelMcp\Contracts;

interface ToolInterface
{
    /**
     * The unique identifying name of the tool (e.g., 'list_routes').
     */
    public function getName(): string;

    /**
     * A description informing the LLM precisely when and why to use this tool.
     */
    public function getDescription(): string;

    /**
     * A valid JSON Schema object definition representing the expected input arguments.
     */
    public function getInputSchema(): array;

    /**
     * Execute the tool logic and return an MCP spec-compliant 'content' block.
     */
    public function execute(array $arguments): array;
}
