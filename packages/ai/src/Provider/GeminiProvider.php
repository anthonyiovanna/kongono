<?php

declare(strict_types=1);

namespace Kongono\Ai\Provider;

use Closure;
use JsonException;
use Kongono\Ai\AiException;
use Kongono\Ai\ChatRequest;
use Kongono\Ai\ChatResponse;
use Kongono\Ai\Embedding\EmbeddingProviderInterface;
use Kongono\Ai\Role;
use Kongono\Ai\TokenUsage;
use Kongono\Ai\ToolCall;
use Kongono\Ai\ToolDefinition;
use Kongono\Ai\Transport\CurlTransport;
use Kongono\Ai\Transport\HttpTransportInterface;
use Kongono\Ai\Transport\StreamingHttpTransportInterface;
use Kongono\Core\Diagnostics\SecretScrubber;
use SensitiveParameter;

/** Google Gemini GenerateContent API provider with live SSE token streaming and embeddings. */
final readonly class GeminiProvider implements EmbeddingProviderInterface, StreamingAiProviderInterface
{
    private HttpTransportInterface $transport;

    public function __construct(
        #[SensitiveParameter]
        private string $apiKey,
        private string $model = 'gemini-2.5-flash',
        private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta',
        ?HttpTransportInterface $transport = null,
    ) {
        $this->transport = $transport ?? new CurlTransport();
    }

    public function name(): string
    {
        return 'gemini';
    }

    public function defaultModel(): string
    {
        return $this->model;
    }

    public function chat(ChatRequest $request): ChatResponse
    {
        $model = $request->model ?? $this->model;
        $systemParts = [];
        $contents = [];

        foreach ($request->messages as $msg) {
            if (Role::System === $msg->role) {
                $systemParts[] = $msg->content;
                continue;
            }
            $role = Role::Assistant === $msg->role ? 'model' : 'user';
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $msg->content]],
            ];
        }

        $payload = ['contents' => $contents];

        if ([] !== $systemParts) {
            $payload['systemInstruction'] = [
                'parts' => [['text' => implode("\n\n", $systemParts)]],
            ];
        }

        $genConfig = [];
        if (null !== $request->temperature) {
            $genConfig['temperature'] = $request->temperature;
        }
        if (null !== $request->maxTokens) {
            $genConfig['maxOutputTokens'] = $request->maxTokens;
        }
        if ($request->jsonMode) {
            $genConfig['responseMimeType'] = 'application/json';
        }
        if ([] !== $genConfig) {
            $payload['generationConfig'] = $genConfig;
        }

        if ([] !== $request->tools) {
            $payload['tools'] = [[
                'functionDeclarations' => array_map(
                    static fn(ToolDefinition $tool): array => [
                        'name' => $tool->name,
                        'description' => $tool->description,
                        'parameters' => $tool->parametersSchema,
                    ],
                    $request->tools,
                ),
            ]];
        }

        $url = sprintf('%s/models/%s:generateContent', rtrim($this->baseUrl, '/'), rawurlencode($model));
        $http = $this->transport->postJson(
            $url,
            ['x-goog-api-key' => $this->apiKey],
            json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
        );

        if ($http->statusCode < 200 || $http->statusCode >= 300) {
            throw new AiException(sprintf(
                'Gemini API returned HTTP %d: %s',
                $http->statusCode,
                SecretScrubber::scrub(substr($http->body, 0, 500)),
            ));
        }

        try {
            $decoded = json_decode($http->body, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new AiException('Failed to decode Gemini JSON response.', previous: $e);
        }

        if (!is_array($decoded) || !isset($decoded['candidates']) || !is_array($decoded['candidates']) || !isset($decoded['candidates'][0]) || !is_array($decoded['candidates'][0])) {
            throw new AiException('Unexpected Gemini response structure: missing candidates[0].');
        }

        $candidate = $decoded['candidates'][0];
        $contentBlock = is_array($candidate['content'] ?? null) ? $candidate['content'] : [];
        $parts = is_array($contentBlock['parts'] ?? null) ? $contentBlock['parts'] : [];
        $textParts = [];
        $toolCalls = [];

        foreach ($parts as $part) {
            if (!is_array($part)) {
                continue;
            }
            if (is_string($part['text'] ?? null)) {
                $textParts[] = $part['text'];
            } elseif (is_array($part['functionCall'] ?? null) && is_string($part['functionCall']['name'] ?? null)) {
                /** @var array<string, mixed> $args */
                $args = is_array($part['functionCall']['args'] ?? null) ? $part['functionCall']['args'] : [];
                $toolCalls[] = new ToolCall('gem_call_' . count($toolCalls), $part['functionCall']['name'], $args);
            }
        }

        $usageRaw = is_array($decoded['usageMetadata'] ?? null) ? $decoded['usageMetadata'] : [];
        $usage = new TokenUsage(
            inputTokens: is_int($usageRaw['promptTokenCount'] ?? null) ? $usageRaw['promptTokenCount'] : 0,
            outputTokens: is_int($usageRaw['candidatesTokenCount'] ?? null) ? $usageRaw['candidatesTokenCount'] : 0,
        );

        return new ChatResponse(
            text: implode('', $textParts),
            model: $model,
            provider: $this->name(),
            toolCalls: $toolCalls,
            usage: $usage,
            finishReason: is_string($candidate['finishReason'] ?? null) ? $candidate['finishReason'] : null,
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
        $contents = [];
        foreach ($request->messages as $msg) {
            if (Role::System === $msg->role) {
                $systemParts[] = $msg->content;
            } else {
                $contents[] = ['role' => Role::Assistant === $msg->role ? 'model' : 'user', 'parts' => [['text' => $msg->content]]];
            }
        }
        $payload = ['contents' => $contents];
        if ([] !== $systemParts) {
            $payload['systemInstruction'] = ['parts' => [['text' => implode("\n\n", $systemParts)]]];
        }

        $url = sprintf('%s/models/%s:streamGenerateContent?alt=sse', rtrim($this->baseUrl, '/'), rawurlencode($model));
        $buffer = '';
        $fullText = '';
        $finishReason = null;

        $status = $this->transport->postJsonStream(
            $url,
            ['x-goog-api-key' => $this->apiKey],
            json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            static function (string $chunk) use (&$buffer, &$fullText, &$finishReason, $onDelta): void {
                $buffer .= str_replace("\r\n", "\n", $chunk);
                if (strlen($buffer) > 1_048_576) {
                    throw new AiException('Gemini streaming line buffer exceeded maximum bound.');
                }
                while (false !== ($pos = strpos($buffer, "\n"))) {
                    $line = trim(substr($buffer, 0, $pos));
                    $buffer = substr($buffer, $pos + 1);
                    if (!str_starts_with($line, 'data:')) {
                        continue;
                    }
                    $frame = json_decode(trim(substr($line, 5)), true);
                    if (!is_array($frame) || !isset($frame['candidates']) || !is_array($frame['candidates']) || !isset($frame['candidates'][0]) || !is_array($frame['candidates'][0])) {
                        continue;
                    }
                    $cand = $frame['candidates'][0];
                    if (is_string($cand['finishReason'] ?? null)) {
                        $finishReason = $cand['finishReason'];
                    }
                    $content = is_array($cand['content'] ?? null) ? $cand['content'] : [];
                    $parts = is_array($content['parts'] ?? null) ? $content['parts'] : [];
                    foreach ($parts as $part) {
                        if (is_array($part) && is_string($part['text'] ?? null) && '' !== $part['text']) {
                            if (strlen($fullText) + strlen($part['text']) > 4_194_304) {
                                throw new AiException('Gemini streaming output exceeded maximum bound.');
                            }
                            $fullText .= $part['text'];
                            $onDelta($part['text']);
                        }
                    }
                }
            },
        );

        if ($status < 200 || $status >= 300) {
            throw new AiException(sprintf('Gemini streaming API returned HTTP %d.', $status));
        }

        return new ChatResponse($fullText, $model, $this->name(), finishReason: $finishReason);
    }

    public function embed(array $texts, ?string $model = null): array
    {
        $embedModel = $model ?? 'text-embedding-004';
        $url = sprintf('%s/models/%s:batchEmbedContents', rtrim($this->baseUrl, '/'), rawurlencode($embedModel));
        $requests = array_map(
            static fn(string $t): array => [
                'model' => 'models/' . $embedModel,
                'content' => ['parts' => [['text' => $t]]],
            ],
            $texts,
        );

        $http = $this->transport->postJson(
            $url,
            ['x-goog-api-key' => $this->apiKey],
            json_encode(['requests' => $requests], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
        );

        if ($http->statusCode < 200 || $http->statusCode >= 300) {
            throw new AiException(sprintf(
                'Gemini Embeddings API returned HTTP %d: %s',
                $http->statusCode,
                SecretScrubber::scrub(substr($http->body, 0, 500)),
            ));
        }

        $decoded = json_decode($http->body, true, 64, JSON_THROW_ON_ERROR);
        if (!is_array($decoded) || !isset($decoded['embeddings']) || !is_array($decoded['embeddings'])) {
            throw new AiException('Unexpected Gemini embeddings response: missing embeddings.');
        }

        $vectors = [];
        foreach ($decoded['embeddings'] as $item) {
            if (!is_array($item) || !isset($item['values']) || !is_array($item['values'])) {
                continue;
            }
            $vec = [];
            foreach ($item['values'] as $n) {
                $vec[] = is_numeric($n) ? (float) $n : 0.0;
            }
            $vectors[] = $vec;
        }

        return $vectors;
    }
}
