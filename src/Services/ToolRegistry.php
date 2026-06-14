<?php

namespace OriginMain\LaravelMcp\Services;

use OriginMain\LaravelMcp\Contracts\ToolInterface;

class ToolRegistry
{
    /**
     * @var array<string, ToolInterface>
     */
    private array $tools = [];

    /**
     * Register a collection of tools into the runtime memory registry.
     */
    public function register(ToolInterface $tool): void
    {
        $this->tools[$tool->getName()] = $tool;
    }

    /**
     * Retrieve all registered tool definitions formatted for the 'tools/list' MCP frame.
     */
    public function listTools(): array
    {
        return array_map(function (ToolInterface $tool) {
            return [
                'name' => $tool->getName(),
                'description' => $tool->getDescription(),
                'inputSchema' => $tool->getInputSchema(),
            ];
        }, array_values($this->tools));
    }

    /**
     * Fetch a concrete tool instance by its registration key name.
     */
    public function get(string $name): ?ToolInterface
    {
        return $this->tools[$name] ?? null;
    }
}
