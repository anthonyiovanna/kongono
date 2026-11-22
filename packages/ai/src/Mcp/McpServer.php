<?php

declare(strict_types=1);

namespace Kongono\Ai\Mcp;

use Closure;
use JsonException;
use Kongono\Ai\Agent\AgentPrincipal;
use Kongono\Ai\ToolDefinition;
use Kongono\Core\Diagnostics\SecretScrubber;
use Throwable;

/**
 * Pure PHP 8.5 Model Context Protocol (MCP) Server (JSON-RPC 2.0).
 *
 * Works over both stdio (`kongo mcp`) and HTTP (`POST /mcp`), governed by an optional
 * AgentPrincipal capability allowlist and automatic SecretScrubber redaction.
 */
final class McpServer
{
    public const string PROTOCOL_VERSION = '2025-03-26';
    public const int MAX_PAYLOAD_BYTES = 1_048_576;

    /** @var array<string, array{definition: ToolDefinition, handler: Closure(array<string, mixed>): string}> */
    private array $tools = [];

    public function __construct(
        private readonly string $serverName = 'kongono-mcp',
        private readonly string $serverVersion = '1.0.0',
        private readonly ?AgentPrincipal $principal = null,
    ) {}

    /**
     * Register a tool callable by external MCP clients (IDEs, AI agents).
     *
     * @param Closure(array<string, mixed>): string $handler
     */
    public function registerTool(ToolDefinition $definition, Closure $handler): self
    {
        if (null !== $this->principal && !$this->principal->allowsTool($definition->name)) {
            return $this;
        }

        $this->tools[$definition->name] = [
            'definition' => $definition,
            'handler' => $handler,
        ];

        return $this;
    }

    /**
     * Handle one JSON-RPC 2.0 request string and return the JSON response string (or null for notifications).
     */
    public function handleJson(string $rawJson): ?string
    {
        if (strlen($rawJson) > self::MAX_PAYLOAD_BYTES) {
            return $this->encodeError(null, -32600, sprintf('Request payload exceeds maximum bound of %d bytes.', self::MAX_PAYLOAD_BYTES));
        }

        if (str_starts_with($rawJson, "\xEF\xBB\xBF")) {
            $rawJson = substr($rawJson, 3);
        }

        try {
            $decoded = json_decode($rawJson, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->encodeError(null, -32700, 'Parse error: invalid JSON.');
        }

        if (!is_array($decoded) || '2.0' !== ($decoded['jsonrpc'] ?? null) || !is_string($decoded['method'] ?? null)) {
            return $this->encodeError(null, -32600, 'Invalid JSON-RPC 2.0 request.');
        }

        $id = $decoded['id'] ?? null;
        $isNotification = !array_key_exists('id', $decoded);
        $method = $decoded['method'];
        /** @var array<string, mixed> $params */
        $params = is_array($decoded['params'] ?? null) ? $decoded['params'] : [];

        if ($isNotification) {
            return null;
        }

        $responseId = is_int($id) || is_string($id) ? $id : null;

        return match ($method) {
            'initialize' => $this->encodeResult($responseId, [
                'protocolVersion' => self::PROTOCOL_VERSION,
                'capabilities' => [
                    'tools' => (object) [],
                ],
                'serverInfo' => [
                    'name' => $this->serverName,
                    'version' => $this->serverVersion,
                ],
            ]),
            'ping' => $this->encodeResult($responseId, (object) []),
            'tools/list' => $this->encodeResult($responseId, [
                'tools' => array_values(array_map(
                    static fn(array $entry): array => [
                        'name' => $entry['definition']->name,
                        'description' => $entry['definition']->description,
                        'inputSchema' => $entry['definition']->parametersSchema,
                    ],
                    $this->tools,
                )),
            ]),
            'tools/call' => $this->handleToolCall($responseId, $params),
            default => $this->encodeError($responseId, -32601, sprintf('Method "%s" not found.', $method)),
        };
    }

    /**
     * Run a standard line-delimited JSON-RPC stdio MCP server loop.
     *
     * Defaults to STDIN/STDOUT when running in CLI SAPI (safe to load in HTTP/worker SAPIs).
     */
    public function serveStdio(mixed $input = null, mixed $output = null): void
    {
        $in = $input ?? (defined('STDIN') ? STDIN : null);
        $out = $output ?? (defined('STDOUT') ? STDOUT : null);
        if (!is_resource($in) || !is_resource($out)) {
            return;
        }

        while (false !== ($line = fgets($in))) {
            $trimmed = trim($line);
            if ('' === $trimmed) {
                continue;
            }
            $response = $this->handleJson($trimmed);
            if (null !== $response) {
                fwrite($out, $response . "\n");
                fflush($out);
            }
        }
    }

    /** @param array<string, mixed> $params */
    private function handleToolCall(int|string|null $id, array $params): string
    {
        $name = is_string($params['name'] ?? null) ? $params['name'] : '';
        if ('' === $name || !isset($this->tools[$name])) {
            return $this->encodeError($id, -32602, sprintf('Unknown or unauthorized MCP tool "%s".', $name));
        }

        if (null !== $this->principal && !$this->principal->allowsTool($name)) {
            return $this->encodeError($id, -32001, sprintf('Capability denied for tool "%s".', $name));
        }

        if (null !== $this->principal && $this->principal->requiresApproval($name)) {
            return $this->encodeError($id, -32002, sprintf('Tool "%s" requires explicit human approval before execution.', $name));
        }

        /** @var array<string, mixed> $arguments */
        $arguments = [];
        if (isset($params['arguments']) && is_array($params['arguments'])) {
            foreach ($params['arguments'] as $k => $v) {
                $arguments[(string) $k] = $v;
            }
        }

        try {
            $rawResult = ($this->tools[$name]['handler'])($arguments);
            $safeResult = SecretScrubber::scrub($rawResult);

            return $this->encodeResult($id, [
                'content' => [[
                    'type' => 'text',
                    'text' => $safeResult,
                ]],
                'isError' => false,
            ]);
        } catch (Throwable $e) {
            return $this->encodeResult($id, [
                'content' => [[
                    'type' => 'text',
                    'text' => 'Tool error: ' . SecretScrubber::scrub($e->getMessage()),
                ]],
                'isError' => true,
            ]);
        }
    }

    private function encodeResult(int|string|null $id, mixed $result): string
    {
        return json_encode([
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    private function encodeError(int|string|null $id, int $code, string $message): string
    {
        return json_encode([
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }
}
