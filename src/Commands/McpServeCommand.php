<?php

namespace OriginMain\LaravelMcp\Commands;

use Illuminate\Console\Command;
use Throwable;

class McpServeCommand extends Command
{
    protected $signature = 'mcp:serve';
    protected $description = 'Start the local Model Context Protocol (MCP) server over stdio';

    private bool $initialized = false;
    private const PROTOCOL_VERSION = '2024-11-05';

    public function handle(): int
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        $this->output->getOutput()->setVerbosity(\Symfony\Component\Console\Output\OutputInterface::VERBOSITY_QUIET);

        $this->logDebug('MCP Server initialized. Awaiting JSON-RPC frames on stdin...');

        while ($line = fgets(STDIN)) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            try {
                $payload = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                $this->processPayload($payload);
            } catch (\JsonException $e) {
                $this->sendError(null, -32700, 'Parse error: Invalid JSON payload.');
            } catch (Throwable $e) {
                $this->logDebug('Critical Failure: ' . $e->getMessage());
                $this->sendError(null, -32603, 'Internal server error occurred.');
            }
        }

        return self::SUCCESS;
    }

    private function processPayload(array $payload): void
    {
        $id = $payload['id'] ?? null;
        $method = $payload['method'] ?? null;

        if (($payload['jsonrpc'] ?? null) !== '2.0') {
            $this->sendError($id, -32600, 'Invalid Request: Missing or incorrect jsonrpc version.');
            return;
        }

        if (!$method) {
            $this->sendError($id, -32600, 'Invalid Request: Missing method parameter.');
            return;
        }

        if (!$this->initialized && !in_array($method, ['initialize', 'notifications/initialized'])) {
            $this->sendError($id, -32002, 'Server not initialized. Call "initialize" first.');
            return;
        }

        switch ($method) {
            case 'initialize':
                $this->handleInitialize($id, $payload['params'] ?? []);
                break;

            case 'notifications/initialized':
                $this->initialized = true;
                $this->logDebug('Handshake fully acknowledged. Server operational.');
                break;

            case 'tools/list':
                $this->handleToolsList($id);
                break;

            default:
                $this->sendError($id, -32601, "Method not found: {$method}");
                break;
        }
    }

    private function handleInitialize(?int $id, array $params): void
    {
        $clientVersion = $params['protocolVersion'] ?? null;

        if ($clientVersion !== self::PROTOCOL_VERSION) {
            $this->logDebug("Protocol mismatch warning. Client requested: {$clientVersion}");
        }

        $response = [
            'protocolVersion' => self::PROTOCOL_VERSION,
            'capabilities' => [
                'tools' => [
                    'listChanged' => false
                ]
            ],
            'serverInfo' => [
                'name' => 'origin-main/laravel-mcp',
                'version' => '1.0.0'
            ]
        ];

        $this->sendResponse($id, $response);
    }

    private function handleToolsList(?int $id): void
    {
        $tools = [
            [
                'name' => 'list_routes',
                'description' => 'Parses the framework router to output clean endpoints, controller mappings, and HTTP methods.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => (object)[]
                ]
            ],
            [
                'name' => 'read_model_schema',
                'description' => 'Inspects database columns, data types, and Eloquent relationships via reflection.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'model' => [
                            'type' => 'string',
                            'description' => 'The fully qualified class name of the Eloquent model (e.g., App\\Models\\User).'
                        ]
                    ],
                    'required' => ['model']
                ]
            ],
            [
                'name' => 'run_safe_artisan',
                'description' => 'Executes a strict whitelist of read-only console operations.',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'command' => [
                            'type' => 'string',
                            'description' => 'The whitelisted artisan command to run (e.g., route:list, config:show).'
                        ]
                    ],
                    'required' => ['command']
                ]
            ]
        ];

        $this->sendResponse($id, ['tools' => $tools]);
    }

    private function sendResponse(?int $id, array $result): void
    {
        if ($id === null) return;

        $response = [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result
        ];

        fwrite(STDOUT, json_encode($response, JSON_UNESCAPED_SLASHES) . "\n");
        fflush(STDOUT);
    }

    private function sendError(?int $id, int $code, string $message, mixed $data = null): void
    {
        $response = [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message
            ]
        ];

        if ($data !== null) {
            $response['error']['data'] = $data;
        }

        fwrite(STDOUT, json_encode($response, JSON_UNESCAPED_SLASHES) . "\n");
        fflush(STDOUT);
    }

    private function logDebug(string $message): void
    {
        fwrite(STDERR, sprintf("[%s] [DEBUG] %s\n", date('Y-m-d H:i:s'), $message));
        fflush(STDERR);
    }
}
