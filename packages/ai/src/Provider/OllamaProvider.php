<?php

declare(strict_types=1);

namespace Kongono\Ai\Provider;

use Closure;
use JsonException;
use Kongono\Ai\AiException;
use Kongono\Ai\ChatRequest;
use Kongono\Ai\ChatResponse;
use Kongono\Ai\Embedding\EmbeddingProviderInterface;
use Kongono\Ai\Message;
use Kongono\Ai\TokenUsage;
use Kongono\Ai\ToolCall;
use Kongono\Ai\ToolDefinition;
use Kongono\Ai\Transport\CurlTransport;
use Kongono\Ai\Transport\HttpTransportInterface;
use Kongono\Ai\Transport\StreamingHttpTransportInterface;
use Kongono\Core\Diagnostics\SecretScrubber;

/** Local / self-hosted Ollama chat & embeddings provider with native NDJSON streaming (zero API key required). */
final readonly class OllamaProvider implements EmbeddingProviderInterface, StreamingAiProviderInterface
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
        $payload = [
            'model' => $model,
            'stream' => true,
            'messages' => array_map(
                static fn(Message $m): array => ['role' => $m->role->value, 'content' => $m->content],
                $request->messages,
            ),
        ];

        $url = rtrim($this->host, '/') . '/api/chat';
        $buffer = '';
        $fullText = '';
        $resolvedModel = $model;
        $finishReason = 'stop';

        $status = $this->transport->postJsonStream(
            $url,
            [],
            json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            static function (string $chunk) use (&$buffer, &$fullText, &$resolvedModel, &$finishReason, $onDelta): void {
                $buffer .= str_replace("\r\n", "\n", $chunk);
                while (false !== ($pos = strpos($buffer, "\n"))) {
                    $line = trim(substr($buffer, 0, $pos));
                    $buffer = substr($buffer, $pos + 1);
                    if ('' === $line) {
                        continue;
                    }
                    $frame = json_decode($line, true);
                    if (!is_array($frame)) {
                        continue;
                    }
                    if (is_string($frame['model'] ?? null)) {
                        $resolvedModel = $frame['model'];
                    }
                    if (is_string($frame['done_reason'] ?? null)) {
                        $finishReason = $frame['done_reason'];
                    }
                    $msg = is_array($frame['message'] ?? null) ? $frame['message'] : [];
                    if (is_string($msg['content'] ?? null) && '' !== $msg['content']) {
                        $fullText .= $msg['content'];
                        $onDelta($msg['content']);
                    }
                }
            },
        );

        if ($status < 200 || $status >= 300) {
            throw new AiException(sprintf('Ollama streaming API returned HTTP %d.', $status));
        }

        return new ChatResponse($fullText, $resolvedModel, $this->name(), finishReason: $finishReason);
    }

    public function embed(array $texts, ?string $model = null): array
    {
        $embedModel = $model ?? 'nomic-embed-text';
        $url = rtrim($this->host, '/') . '/api/embed';
        $http = $this->transport->postJson(
            $url,
            [],
            json_encode(['model' => $embedModel, 'input' => $texts], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
        );

        if ($http->statusCode < 200 || $http->statusCode >= 300) {
            throw new AiException(sprintf(
                'Ollama Embeddings API returned HTTP %d: %s',
                $http->statusCode,
                SecretScrubber::scrub(substr($http->body, 0, 500)),
            ));
        }

        $decoded = json_decode($http->body, true, 64, JSON_THROW_ON_ERROR);
        if (!is_array($decoded) || !isset($decoded['embeddings']) || !is_array($decoded['embeddings'])) {
            throw new AiException('Unexpected Ollama embeddings response: missing embeddings.');
        }

        $vectors = [];
        foreach ($decoded['embeddings'] as $item) {
            if (!is_array($item)) {
                continue;
            }
            $vec = [];
            foreach ($item as $n) {
                $vec[] = is_numeric($n) ? (float) $n : 0.0;
            }
            $vectors[] = $vec;
        }

        return $vectors;
    }
}
