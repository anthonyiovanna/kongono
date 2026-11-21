<?php

declare(strict_types=1);

namespace Kongono\Ai\Provider;

use JsonException;
use Kongono\Ai\AiException;
use Kongono\Ai\ChatRequest;
use Kongono\Ai\ChatResponse;
use Kongono\Ai\Message;
use Kongono\Ai\TokenUsage;
use Kongono\Ai\ToolCall;
use Kongono\Ai\ToolDefinition;
use Kongono\Ai\Transport\CurlTransport;
use Kongono\Ai\Transport\HttpTransportInterface;
use Kongono\Core\Diagnostics\SecretScrubber;

/** Local / self-hosted Ollama chat provider (zero API key required). */
final readonly class OllamaProvider implements AiProviderInterface
{
    private HttpTransportInterface $transport;

    public function __construct(
        private string $model = 'qwen2.5-coder:7b',
        private string $host = 'http://127.0.0.1:11434',
        ?HttpTransportInterface $transport = null,
    ) {
        $this->transport = $transport ?? new CurlTransport();
    }

    public function name(): string
    {
        return 'ollama';
    }

    public function defaultModel(): string
    {
        return $this->model;
    }

    public function chat(ChatRequest $request): ChatResponse
    {
        $model = $request->model ?? $this->model;
        $payload = [
            'model' => $model,
            'stream' => false,
            'messages' => array_map(
                static fn(Message $m): array => [
                    'role' => $m->role->value,
                    'content' => $m->content,
                ],
                $request->messages,
            ),
        ];

        if ($request->jsonMode) {
            $payload['format'] = 'json';
        }
        if (null !== $request->temperature || null !== $request->maxTokens) {
            $options = [];
            if (null !== $request->temperature) {
                $options['temperature'] = $request->temperature;
            }
            if (null !== $request->maxTokens) {
                $options['num_predict'] = $request->maxTokens;
            }
            $payload['options'] = $options;
        }
        if ([] !== $request->tools) {
            $payload['tools'] = array_map(
                static fn(ToolDefinition $tool): array => [
                    'type' => 'function',
                    'function' => [
                        'name' => $tool->name,
                        'description' => $tool->description,
                        'parameters' => $tool->parametersSchema,
                    ],
                ],
                $request->tools,
            );
        }

        $url = rtrim($this->host, '/') . '/api/chat';
        $http = $this->transport->postJson($url, [], json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), timeoutSeconds: 120);

        if ($http->statusCode < 200 || $http->statusCode >= 300) {
            throw new AiException(sprintf(
                'Ollama API returned HTTP %d: %s',
                $http->statusCode,
                SecretScrubber::scrub(substr($http->body, 0, 500)),
            ));
        }

        try {
            $decoded = json_decode($http->body, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new AiException('Failed to decode Ollama JSON response.', previous: $e);
        }

        if (!is_array($decoded) || !isset($decoded['message']) || !is_array($decoded['message'])) {
            throw new AiException('Unexpected Ollama response structure: missing message.');
        }

        $msg = $decoded['message'];
        $text = is_string($msg['content'] ?? null) ? $msg['content'] : '';
        $toolCalls = [];
        if (isset($msg['tool_calls']) && is_array($msg['tool_calls'])) {
            foreach ($msg['tool_calls'] as $rawCall) {
                if (!is_array($rawCall) || !is_array($rawCall['function'] ?? null) || !is_string($rawCall['function']['name'] ?? null)) {
                    continue;
                }
                /** @var array<string, mixed> $args */
                $args = is_array($rawCall['function']['arguments'] ?? null) ? $rawCall['function']['arguments'] : [];
                $toolCalls[] = new ToolCall('ollama_call_' . count($toolCalls), $rawCall['function']['name'], $args);
            }
        }

        $usage = new TokenUsage(
            inputTokens: is_int($decoded['prompt_eval_count'] ?? null) ? $decoded['prompt_eval_count'] : 0,
            outputTokens: is_int($decoded['eval_count'] ?? null) ? $decoded['eval_count'] : 0,
        );

        return new ChatResponse(
            text: $text,
            model: is_string($decoded['model'] ?? null) ? $decoded['model'] : $model,
            provider: $this->name(),
            toolCalls: $toolCalls,
            usage: $usage,
            finishReason: is_string($decoded['done_reason'] ?? null) ? $decoded['done_reason'] : 'stop',
        );
    }
}
