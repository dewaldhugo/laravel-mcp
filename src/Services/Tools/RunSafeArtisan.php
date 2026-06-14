<?php

namespace OriginMain\LaravelMcp\Services\Tools;

use Illuminate\Support\Facades\Artisan;
use OriginMain\LaravelMcp\Contracts\ToolInterface;
use Throwable;

class RunSafeArtisan implements ToolInterface
{
    /**
     * Strict immutable whitelist of safe, read-only internal console operations.
     */
    private const WHITELIST = [
        'about',
        'route:list',
        'config:show',
        'model:show',
    ];

    public function getName(): string
    {
        return 'run_safe_artisan';
    }

    public function getDescription(): string
    {
        return 'Executes a strict whitelist of read-only Laravel Artisan diagnostics commands. Approved operations: about, route:list, config:show, model:show.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'command' => [
                    'type' => 'string',
                    'description' => 'The whitelisted command to execute (e.g., "about" or "route:list").'
                ],
                'arguments' => [
                    'type' => 'object',
                    'description' => 'Optional arguments or options to pass to the command as key-value pairs.',
                    'properties' => (object)[]
                ]
            ],
            'required' => ['command']
        ];
    }

    public function execute(array $arguments): array
    {
        $command = trim($arguments['command'] ?? '');
        $parameters = $arguments['arguments'] ?? [];

        // Security Boundary: Block commands completely if they fail basic signature matches
        if (!in_array($command, self::WHITELIST, true)) {
            return [
                'isError' => true,
                'content' => [
                    [
                        'type' => 'text',
                        'text' => "Security Exception: Command '{$command}' is not whitelisted for remote execution."
                    ]
                ]
            ];
        }

        try {
            // Execute inside the framework kernel buffer to prevent echoing to raw STDOUT streams
            $exitCode = Artisan::call($command, $parameters);
            $output = Artisan::output();

            return [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => "Exit Code: {$exitCode}\n\nOutput:\n{$output}"
                    ]
                ]
            ];
        } catch (Throwable $e) {
            return [
                'isError' => true,
                'content' => [
                    [
                        'type' => 'text',
                        'text' => "Artisan Execution Failure: " . $e->getMessage()
                    ]
                ]
            ];
        }
    }
}
