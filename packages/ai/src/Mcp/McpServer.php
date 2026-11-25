<?php

declare(strict_types=1);

namespace Kongono\Ai\Mcp;

use Closure;
use JsonException;
use Kongono\Ai\Agent\AgentPrincipal;
use Kongono\Ai\Agent\RequiresApproval;
use Kongono\Ai\ToolDefinition;
use Kongono\Core\Diagnostics\SecretScrubber;
use ReflectionFunction;
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

    /** @var array<string, array{definition: ToolDefinition, handler: Closure(array<string, mixed>): string, requiresApproval: bool}> */
    private array $tools = [];

    private int $toolCallsExecuted = 0;

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

        $refHandler = new ReflectionFunction($handler);
        $scopeClass = $refHandler->getClosureScopeClass();
        $calledClass = $refHandler->getClosureCalledClass();
        $handlerAttr = [] !== $refHandler->getAttributes(RequiresApproval::class)
            || (null !== $scopeClass && [] !== $scopeClass->getAttributes(RequiresApproval::class))
            || (null !== $calledClass && [] !== $calledClass->getAttributes(RequiresApproval::class));
        $priorApproval = isset($this->tools[$definition->name]) && $this->tools[$definition->name]['requiresApproval'];
        $this->tools[$definition->name] = [
            'definition' => $definition,
            'handler' => $handler,
            'requiresApproval' => $priorApproval || $definition->requiresApproval || $handlerAttr,
        ];

        return $this;
    }

    /** Reset the per-session executed tool call counter (useful between isolated HTTP requests or stdio sessions). */
    public function resetBudget(): void
    {
        $this->toolCallsExecuted = 0;
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
        if (!$isNotification && !is_int($id) && !is_string($id) && null !== $id) {
            return $this->encodeError(null, -32600, 'Invalid JSON-RPC 2.0 request id: expected string, integer, or null.');
        }
        $method = $decoded['method'];
        $responseId = is_int($id) || is_string($id) ? $id : null;

        if (array_key_exists('params', $decoded) && null !== $decoded['params'] && !is_array($decoded['params'])) {
            return $isNotification ? null : $this->encodeError($responseId, -32602, 'Invalid params: expected JSON object or array.');
        }

        /** @var array<string, mixed> $params */
        $params = is_array($decoded['params'] ?? null) ? $decoded['params'] : [];

        if ($isNotification) {
            return null;
        }

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

        while (!feof($in)) {
            $chunk = fgets($in, self::MAX_PAYLOAD_BYTES + 2);
            if (false === $chunk) {
                break;
            }
            if (strlen($chunk) > self::MAX_PAYLOAD_BYTES && !str_ends_with($chunk, "\n")) {
                // Drain remainder of oversized line without buffering it into RAM
                while (!feof($in)) {
                    $discard = fgets($in, 65536);
                    if (false === $discard || str_ends_with($discard, "\n")) {
                        break;
                    }
                }
                fwrite($out, $this->encodeError(null, -32600, sprintf('Request payload exceeds maximum bound of %d bytes.', self::MAX_PAYLOAD_BYTES)) . "\n");
                fflush($out);
                continue;
            }
            $trimmed = trim($chunk);
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

        if ($this->tools[$name]['requiresApproval'] || (null !== $this->principal && $this->principal->requiresApproval($name))) {
            return $this->encodeError($id, -32002, sprintf('Tool "%s" requires explicit human approval before execution.', $name));
        }

        if (null !== $this->principal && $this->toolCallsExecuted >= $this->principal->maxToolCalls) {
            return $this->encodeError($id, -32003, sprintf('MCP tool call budget exceeded (%d max calls).', $this->principal->maxToolCalls));
        }

        if (
            array_key_exists('arguments', $params)
            && null !== $params['arguments']
            && (!is_array($params['arguments']) || ([] !== $params['arguments'] && array_is_list($params['arguments'])))
        ) {
            return $this->encodeError($id, -32602, sprintf('Invalid arguments for tool "%s": expected JSON object.', $name));
        }

        /** @var array<string, mixed> $arguments */
        $arguments = [];
        if (isset($params['arguments']) && is_array($params['arguments'])) {
            foreach ($params['arguments'] as $k => $v) {
                $arguments[(string) $k] = $v;
            }
        }

        $schemaError = self::validateArgumentsAgainstSchema($arguments, $this->tools[$name]['definition']->parametersSchema);
        if (null !== $schemaError) {
            return $this->encodeError($id, -32602, sprintf('Invalid arguments for tool "%s": %s', $name, $schemaError));
        }

        ++$this->toolCallsExecuted;

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

    /**
     * Validate tool call arguments against JSON Schema `required` and top-level `properties.<k>.type`.
     *
     * @param array<string, mixed> $arguments
     * @param array<string, mixed> $schema
     */
    private static function validateArgumentsAgainstSchema(array $arguments, array $schema): ?string
    {
        if (isset($schema['required']) && is_array($schema['required'])) {
            foreach ($schema['required'] as $reqField) {
                if (is_string($reqField) && !array_key_exists($reqField, $arguments)) {
                    return sprintf('missing required argument "%s".', $reqField);
                }
            }
        }

        $props = is_array($schema['properties'] ?? null) ? $schema['properties'] : [];
        foreach ($arguments as $key => $val) {
            if (!isset($props[$key]) || !is_array($props[$key])) {
                continue;
            }
            $rawType = $props[$key]['type'] ?? null;
            /** @var list<string> $allowedTypes */
            $allowedTypes = [];
            if (is_string($rawType)) {
                $allowedTypes[] = strtolower($rawType);
            } elseif (is_array($rawType)) {
                foreach ($rawType as $t) {
                    if (is_string($t)) {
                        $allowedTypes[] = strtolower($t);
                    }
                }
            }
            if ([] !== $allowedTypes) {
                $valid = false;
                foreach ($allowedTypes as $expectedType) {
                    $matches = match ($expectedType) {
                        'null' => null === $val,
                        'string' => is_string($val),
                        'integer' => is_int($val),
                        'number' => is_int($val) || is_float($val),
                        'boolean' => is_bool($val),
                        'array' => is_array($val) && array_is_list($val),
                        'object' => is_array($val) && ([] === $val || !array_is_list($val)),
                        default => true,
                    };
                    if ($matches) {
                        $valid = true;
                        break;
                    }
                }
                if (!$valid) {
                    return sprintf('argument "%s" must be of type %s.', $key, implode('|', $allowedTypes));
                }
            }
            if (isset($props[$key]['enum']) && is_array($props[$key]['enum']) && !in_array($val, $props[$key]['enum'], true)) {
                return sprintf('argument "%s" is not in allowed enum values.', $key);
            }
        }

        return null;
    }

    private function encodeResult(int|string|null $id, mixed $result): string
    {
        return json_encode([
            'jsonrpc' => '2.0',
            'id' => $id,
            'result' => $result,
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
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
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
