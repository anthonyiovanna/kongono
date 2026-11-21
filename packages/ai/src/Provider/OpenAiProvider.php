<?php

declare(strict_types=1);

namespace Kongono\Ai\Provider;

use JsonException;
use Kongono\Ai\AiException;
use Kongono\Ai\ChatRequest;
use Kongono\Ai\ChatResponse;
use Kongono\Ai\Message;
use Kongono\Ai\Role;
use Kongono\Ai\TokenUsage;
use Kongono\Ai\ToolCall;
use Kongono\Ai\ToolDefinition;
use Kongono\Ai\Transport\CurlTransport;
use Kongono\Ai\Transport\HttpTransportInterface;
use Kongono\Core\Diagnostics\SecretScrubber;
use SensitiveParameter;

/**
 * OpenAI & OpenAI-compatible provider (OpenAI, OpenRouter, Groq, vLLM, LM Studio).
 * Forward-compatible JSON decoding: ignores unknown extra fields cleanly.
 */
final readonly class OpenAiProvider implements AiProviderInterface
{
    private HttpTransportInterface $transport;

    public function __construct(
        #[SensitiveParameter]
        private string $apiKey,
        private string $model = 'gpt-4.1-mini',
        private string $baseUrl = 'https://api.openai.com/v1',
        ?HttpTransportInterface $transport = null,
    ) {
        $this->transport = $transport ?? new CurlTransport();
    }

    public function name(): string
    {
        return 'openai';
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
            'messages' => array_map($this->encodeMessage(...), $request->messages),
        ];

        if (null !== $request->temperature) {
            $payload['temperature'] = $request->temperature;
        }
        if (null !== $request->maxTokens) {
            $payload['max_tokens'] = $request->maxTokens;
        }
        if ($request->jsonMode) {
            $payload['response_format'] = ['type' => 'json_object'];
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

        $headers = [];
        if ('' !== $this->apiKey) {
            $headers['Authorization'] = 'Bearer ' . $this->apiKey;
        }

        $url = rtrim($this->baseUrl, '/') . '/chat/completions';
        $http = $this->transport->postJson($url, $headers, json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));

        if ($http->statusCode < 200 || $http->statusCode >= 300) {
            throw new AiException(sprintf(
                'OpenAI API returned HTTP %d: %s',
                $http->statusCode,
                SecretScrubber::scrub(substr($http->body, 0, 500)),
            ));
        }

        try {
            $decoded = json_decode($http->body, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new AiException('Failed to decode OpenAI JSON response.', previous: $e);
        }

        if (!is_array($decoded) || !isset($decoded['choices']) || !is_array($decoded['choices']) || !isset($decoded['choices'][0]) || !is_array($decoded['choices'][0])) {
            throw new AiException('Unexpected OpenAI response structure: missing choices[0].');
        }

        $choice = $decoded['choices'][0];
        $message = is_array($choice['message'] ?? null) ? $choice['message'] : [];
        $text = is_string($message['content'] ?? null) ? $message['content'] : '';
        $finishReason = is_string($choice['finish_reason'] ?? null) ? $choice['finish_reason'] : null;

        $toolCalls = [];
        if (isset($message['tool_calls']) && is_array($message['tool_calls'])) {
            foreach ($message['tool_calls'] as $rawCall) {
                if (!is_array($rawCall) || !is_array($rawCall['function'] ?? null)) {
                    continue;
                }
                $id = is_string($rawCall['id'] ?? null) ? $rawCall['id'] : 'call_' . count($toolCalls);
                $fnName = is_string($rawCall['function']['name'] ?? null) ? $rawCall['function']['name'] : '';
                $rawArgs = $rawCall['function']['arguments'] ?? '{}';
                $args = [];
                if (is_string($rawArgs) && '' !== trim($rawArgs)) {
                    $parsedArgs = json_decode($rawArgs, true);
                    if (is_array($parsedArgs)) {
                        /** @var array<string, mixed> $args */
                        $args = $parsedArgs;
                    }
                } elseif (is_array($rawArgs)) {
                    /** @var array<string, mixed> $args */
                    $args = $rawArgs;
                }
                if ('' !== $fnName) {
                    $toolCalls[] = new ToolCall($id, $fnName, $args);
                }
            }
        }

        $usageRaw = is_array($decoded['usage'] ?? null) ? $decoded['usage'] : [];
        $usage = new TokenUsage(
            inputTokens: is_int($usageRaw['prompt_tokens'] ?? null) ? $usageRaw['prompt_tokens'] : 0,
            outputTokens: is_int($usageRaw['completion_tokens'] ?? null) ? $usageRaw['completion_tokens'] : 0,
        );

        return new ChatResponse(
            text: $text,
            model: is_string($decoded['model'] ?? null) ? $decoded['model'] : $model,
            provider: $this->name(),
            toolCalls: $toolCalls,
            usage: $usage,
            finishReason: $finishReason,
        );
    }

    /** @return array<string, mixed> */
    private function encodeMessage(Message $message): array
    {
        $encoded = [
            'role' => $message->role->value,
            'content' => $message->content,
        ];

        if (Role::Tool === $message->role && null !== $message->toolCallId) {
            $encoded['tool_call_id'] = $message->toolCallId;
        }
        if (Role::Assistant === $message->role && [] !== $message->toolCalls) {
            $encoded['tool_calls'] = array_map(
                static fn(ToolCall $c): array => [
                    'id' => $c->id,
                    'type' => 'function',
                    'function' => [
                        'name' => $c->name,
                        'arguments' => json_encode($c->arguments, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
                    ],
                ],
                $message->toolCalls,
            );
        }

        return $encoded;
    }
}
