<?php

declare(strict_types=1);

namespace Kongono\Ai\Provider;

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
use Kongono\Core\Diagnostics\SecretScrubber;
use SensitiveParameter;

/** Google Gemini GenerateContent API provider. */
final readonly class GeminiProvider implements AiProviderInterface
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
}
