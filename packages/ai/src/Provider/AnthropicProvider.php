<?php

declare(strict_types=1);

namespace Kongono\Ai\Provider;

use Closure;
use JsonException;
use Kongono\Ai\AiException;
use Kongono\Ai\ChatRequest;
use Kongono\Ai\ChatResponse;
use Kongono\Ai\Role;
use Kongono\Ai\TokenUsage;
use Kongono\Ai\ToolCall;
use Kongono\Ai\ToolDefinition;
use Kongono\Ai\Transport\CurlTransport;
use Kongono\Ai\Transport\HttpTransportInterface;
use Kongono\Ai\Transport\StreamingHttpTransportInterface;
use Kongono\Core\Diagnostics\SecretScrubber;
use SensitiveParameter;

/** Anthropic Claude Messages API provider with live SSE token streaming. */
final readonly class AnthropicProvider implements StreamingAiProviderInterface
{
    private HttpTransportInterface $transport;

    public function __construct(
        #[SensitiveParameter]
        private string $apiKey,
        private string $model = 'claude-sonnet-4-20250514',
        private string $baseUrl = 'https://api.anthropic.com/v1',
        private string $anthropicVersion = '2023-06-01',
        ?HttpTransportInterface $transport = null,
    ) {
        $this->transport = $transport ?? new CurlTransport();
    }

    public function name(): string
    {
        return 'anthropic';
    }

    public function defaultModel(): string
    {
        return $this->model;
    }

    public function chat(ChatRequest $request): ChatResponse
    {
        $model = $request->model ?? $this->model;
        $systemParts = [];
        $messages = [];

        foreach ($request->messages as $msg) {
            if (Role::System === $msg->role) {
                $systemParts[] = $msg->content;
                continue;
            }
            if (Role::Tool === $msg->role && null !== $msg->toolCallId) {
                $messages[] = [
                    'role' => 'user',
                    'content' => [[
                        'type' => 'tool_result',
                        'tool_use_id' => $msg->toolCallId,
                        'content' => $msg->content,
                    ]],
                ];
                continue;
            }
            if (Role::Assistant === $msg->role && [] !== $msg->toolCalls) {
                $blocks = [];
                if ('' !== $msg->content) {
                    $blocks[] = ['type' => 'text', 'text' => $msg->content];
                }
                foreach ($msg->toolCalls as $call) {
                    $blocks[] = [
                        'type' => 'tool_use',
                        'id' => $call->id,
                        'name' => $call->name,
                        'input' => [] === $call->arguments ? (object) [] : $call->arguments,
                    ];
                }
                $messages[] = ['role' => 'assistant', 'content' => $blocks];
                continue;
            }

            $messages[] = [
                'role' => Role::Assistant === $msg->role ? 'assistant' : 'user',
                'content' => $msg->content,
            ];
        }

        $payload = [
            'model' => $model,
            'max_tokens' => $request->maxTokens ?? 4096,
            'messages' => $messages,
        ];

        if ([] !== $systemParts) {
            $payload['system'] = implode("\n\n", $systemParts);
        }
        if (null !== $request->temperature) {
            $payload['temperature'] = $request->temperature;
        }
        if ([] !== $request->tools) {
            $payload['tools'] = array_map(
                static fn(ToolDefinition $tool): array => [
                    'name' => $tool->name,
                    'description' => $tool->description,
                    'input_schema' => $tool->parametersSchema,
                ],
                $request->tools,
            );
        }

        $url = rtrim($this->baseUrl, '/') . '/messages';
        $http = $this->transport->postJson(
            $url,
            [
                'x-api-key' => $this->apiKey,
                'anthropic-version' => $this->anthropicVersion,
            ],
            json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
        );

        if ($http->statusCode < 200 || $http->statusCode >= 300) {
            throw new AiException(sprintf(
                'Anthropic API returned HTTP %d: %s',
                $http->statusCode,
                SecretScrubber::scrub(substr($http->body, 0, 500)),
            ));
        }

        try {
            $decoded = json_decode($http->body, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new AiException('Failed to decode Anthropic JSON response.', previous: $e);
        }

        if (!is_array($decoded) || !isset($decoded['content']) || !is_array($decoded['content'])) {
            throw new AiException('Unexpected Anthropic response structure: missing content.');
        }

        $textParts = [];
        $toolCalls = [];
        foreach ($decoded['content'] as $block) {
            if (!is_array($block)) {
                continue;
            }
            if ('text' === ($block['type'] ?? null) && is_string($block['text'] ?? null)) {
                $textParts[] = $block['text'];
            } elseif ('tool_use' === ($block['type'] ?? null) && is_string($block['name'] ?? null)) {
                $id = is_string($block['id'] ?? null) ? $block['id'] : 'toolu_' . count($toolCalls);
                /** @var array<string, mixed> $args */
                $args = is_array($block['input'] ?? null) ? $block['input'] : [];
                $toolCalls[] = new ToolCall($id, $block['name'], $args);
            }
        }

        $usageRaw = is_array($decoded['usage'] ?? null) ? $decoded['usage'] : [];
        $usage = new TokenUsage(
            inputTokens: is_int($usageRaw['input_tokens'] ?? null) ? $usageRaw['input_tokens'] : 0,
            outputTokens: is_int($usageRaw['output_tokens'] ?? null) ? $usageRaw['output_tokens'] : 0,
        );

        return new ChatResponse(
            text: implode('', $textParts),
            model: is_string($decoded['model'] ?? null) ? $decoded['model'] : $model,
            provider: $this->name(),
            toolCalls: $toolCalls,
            usage: $usage,
            finishReason: is_string($decoded['stop_reason'] ?? null) ? $decoded['stop_reason'] : null,
        );
    }

    public function chatStream(ChatRequest $request, Closure $onDelta): ChatResponse
    {
        if (!$this->transport instanceof StreamingHttpTransportInterface) {
            $response = $this->chat($request);
            if ('' !== $response->text) {
                $onDelta($response->text);
            }

            return $response;
        }

        $model = $request->model ?? $this->model;
        $systemParts = [];
        $messages = [];
        foreach ($request->messages as $msg) {
            if (Role::System === $msg->role) {
                $systemParts[] = $msg->content;
            } else {
                $messages[] = ['role' => Role::Assistant === $msg->role ? 'assistant' : 'user', 'content' => $msg->content];
            }
        }

        $payload = [
            'model' => $model,
            'max_tokens' => $request->maxTokens ?? 4096,
            'stream' => true,
            'messages' => $messages,
        ];
        if ([] !== $systemParts) {
            $payload['system'] = implode("\n\n", $systemParts);
        }

        $url = rtrim($this->baseUrl, '/') . '/messages';
        $buffer = '';
        $fullText = '';
        $finishReason = null;

        $status = $this->transport->postJsonStream(
            $url,
            ['x-api-key' => $this->apiKey, 'anthropic-version' => $this->anthropicVersion],
            json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            static function (string $chunk) use (&$buffer, &$fullText, &$finishReason, $onDelta): void {
                $buffer .= str_replace("\r\n", "\n", $chunk);
                if (strlen($buffer) > 1_048_576) {
                    throw new AiException('Anthropic streaming line buffer exceeded maximum bound.');
                }
                while (false !== ($pos = strpos($buffer, "\n"))) {
                    $line = trim(substr($buffer, 0, $pos));
                    $buffer = substr($buffer, $pos + 1);
                    if (!str_starts_with($line, 'data:')) {
                        continue;
                    }
                    $frame = json_decode(trim(substr($line, 5)), true);
                    if (!is_array($frame)) {
                        continue;
                    }
                    $type = $frame['type'] ?? null;
                    if ('content_block_delta' === $type && is_array($frame['delta'] ?? null) && is_string($frame['delta']['text'] ?? null)) {
                        $delta = $frame['delta']['text'];
                        if (strlen($fullText) + strlen($delta) > 4_194_304) {
                            throw new AiException('Anthropic streaming output exceeded maximum bound.');
                        }
                        $fullText .= $delta;
                        $onDelta($delta);
                    } elseif ('message_delta' === $type && is_array($frame['delta'] ?? null) && is_string($frame['delta']['stop_reason'] ?? null)) {
                        $finishReason = $frame['delta']['stop_reason'];
                    }
                }
            },
        );

        if ($status < 200 || $status >= 300) {
            throw new AiException(sprintf('Anthropic streaming API returned HTTP %d.', $status));
        }

        return new ChatResponse($fullText, $model, $this->name(), finishReason: $finishReason);
    }
}
