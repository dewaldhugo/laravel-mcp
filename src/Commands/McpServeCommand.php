<?php

namespace DewaldHugo\LaravelMcp\Commands;

use Illuminate\Console\Command;
use DewaldHugo\LaravelMcp\Services\ToolRegistry;
use Throwable;

class McpServeCommand extends Command
{
    protected $signature = 'mcp:serve';
    protected $description = 'Start the local Model Context Protocol (MCP) server over stdio';

    private bool $initialized = false;
    private const PROTOCOL_VERSION = '2025-11-25';

    public mixed $inputStream = null;
    public mixed $outputStream = null;
    public mixed $errorStream = null;

    private ToolRegistry $registry;

    public function __construct(ToolRegistry $registry)
    {
        parent::__construct();
        $this->registry = $registry;
    }

    public function handle(): int
    {
        ini_set('memory_limit', '-1');
        set_time_limit(0);

        $this->output->getOutput()->setVerbosity(\Symfony\Component\Console\Output\OutputInterface::VERBOSITY_QUIET);

        $input = $this->inputStream ?? STDIN;

        $this->logDebug('MCP Server initialized. Awaiting JSON-RPC frames on stdin...');

        while ($line = fgets($input)) {
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
                $this->sendResponse($id, ['tools' => $this->registry->listTools()]);
                break;

            case 'tools/call':
                $this->handleToolExecution($id, $payload['params'] ?? []);
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

    private function handleToolExecution(?int $id, array $params): void
    {
        $name = $params['name'] ?? null;
        $arguments = $params['arguments'] ?? [];

        if (!$name) {
            $this->sendError($id, -32602, 'Invalid params: Missing execution tool name.');
            return;
        }

        $tool = $this->registry->get($name);

        if (!$tool) {
            $this->sendError($id, -32601, "Tool execution targeting unknown context: {$name}");
            return;
        }

        try {
            $result = $tool->execute($arguments);
            $this->sendResponse($id, $result);
        } catch (Throwable $e) {
            $this->logDebug("Tool Execution Failure [{$name}]: " . $e->getMessage());
            $this->sendError($id, -32603, "Tool execution encountered an internal error.");
        }
    }

    private function sendResponse(?int $id, array $result): void
    {
        if ($id === null) return;

        $response = [
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result
        ];

        $output = $this->outputStream ?? STDOUT;
        fwrite($output, json_encode($response, JSON_UNESCAPED_SLASHES) . "\n");
        fflush($output);
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

        $output = $this->outputStream ?? STDOUT;
        fwrite($output, json_encode($response, JSON_UNESCAPED_SLASHES) . "\n");
        fflush($output);
    }

    private function logDebug(string $message): void
    {
        $error = $this->errorStream ?? STDERR;
        fwrite($error, sprintf("[%s] [DEBUG] %s\n", date('Y-m-d H:i:s'), $message));
        fflush($error);
    }
}
