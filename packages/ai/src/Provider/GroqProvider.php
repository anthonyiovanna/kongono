<?php

declare(strict_types=1);

namespace Kongono\Ai\Provider;

use Closure;
use Kongono\Ai\ChatRequest;
use Kongono\Ai\ChatResponse;
use Kongono\Ai\Transport\HttpTransportInterface;
use SensitiveParameter;

/**
 * Native Groq LPU provider (OpenAI-compatible chat & SSE token streaming over `api.groq.com/openai/v1`).
 */
final readonly class GroqProvider implements StreamingAiProviderInterface
{
    public const string DEFAULT_MODEL = 'llama-3.3-70b-versatile';
    public const string DEFAULT_BASE_URL = 'https://api.groq.com/openai/v1';

    private OpenAiProvider $delegate;

    public function __construct(
        #[SensitiveParameter]
        string $apiKey,
        private string $model = self::DEFAULT_MODEL,
        string $baseUrl = self::DEFAULT_BASE_URL,
        ?HttpTransportInterface $transport = null,
    ) {
        $this->delegate = new OpenAiProvider(
            apiKey: $apiKey,
            model: $this->model,
            baseUrl: $baseUrl,
            transport: $transport,
            providerName: 'groq',
        );
    }

    public function name(): string
    {
        return 'groq';
    }

    public function defaultModel(): string
    {
        return $this->model;
    }

    public function chat(ChatRequest $request): ChatResponse
    {
        return $this->delegate->chat($request);
    }

    public function chatStream(ChatRequest $request, Closure $onDelta): ChatResponse
    {
        return $this->delegate->chatStream($request, $onDelta);
    }
}
