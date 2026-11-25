<?php

declare(strict_types=1);

namespace Kongono\Ai\Provider;

use Closure;
use Kongono\Ai\AiException;
use Kongono\Ai\ChatRequest;
use Kongono\Ai\ChatResponse;
use Kongono\Ai\Embedding\EmbeddingProviderInterface;
use Kongono\Core\Diagnostics\SecretScrubber;

/**
 * Resilient multi-provider AI router with bounded transient retries (HTTP 429/5xx/network)
 * and automatic failover across ordered providers (e.g. Anthropic -> OpenAI -> Groq -> Ollama).
 */
final readonly class FallbackProvider implements EmbeddingProviderInterface, StreamingAiProviderInterface
{
    /**
     * @param non-empty-list<AiProviderInterface> $providers
     * @param (Closure(int): void)|null           $sleeper   Optional backoff callback receiving milliseconds (defaults to usleep)
     */
    public function __construct(
        private array $providers,
        private int $maxRetriesPerProvider = 1,
        private int $baseBackoffMs = 200,
        private ?Closure $sleeper = null,
    ) {}

    public function name(): string
    {
        return $this->providers[0]->name();
    }

    public function defaultModel(): string
    {
        return $this->providers[0]->defaultModel();
    }

    /** @return non-empty-list<string> */
    public function providerNames(): array
    {
        return array_map(static fn(AiProviderInterface $p): string => $p->name(), $this->providers);
    }

    public function chat(ChatRequest $request): ChatResponse
    {
        $errors = [];

        foreach ($this->providers as $idx => $provider) {
            $providerRequest = 0 === $idx ? $request : $this->adaptRequestForFallback($request, $provider);
            $attempts = 1 + max(0, $this->maxRetriesPerProvider);

            for ($attempt = 1; $attempt <= $attempts; ++$attempt) {
                try {
                    return $provider->chat($providerRequest);
                } catch (AiException $e) {
                    $errors[] = sprintf('%s (attempt %d): %s', $provider->name(), $attempt, SecretScrubber::scrub($e->getMessage()));
                    if ($attempt < $attempts && self::isTransient($e)) {
                        $this->sleepBackoff($attempt);
                        continue;
                    }
                    break;
                }
            }
        }

        throw new AiException('All configured AI providers failed: ' . implode(' | ', $errors));
    }

    public function chatStream(ChatRequest $request, Closure $onDelta): ChatResponse
    {
        $errors = [];

        foreach ($this->providers as $idx => $provider) {
            $providerRequest = 0 === $idx ? $request : $this->adaptRequestForFallback($request, $provider);
            $attempts = 1 + max(0, $this->maxRetriesPerProvider);

            for ($attempt = 1; $attempt <= $attempts; ++$attempt) {
                $emittedAny = false;
                $trackedDelta = static function (string $delta) use ($onDelta, &$emittedAny): void {
                    if ('' !== $delta) {
                        $emittedAny = true;
                        $onDelta($delta);
                    }
                };

                try {
                    if ($provider instanceof StreamingAiProviderInterface) {
                        return $provider->chatStream($providerRequest, $trackedDelta);
                    }
                    $response = $provider->chat($providerRequest);
                    if ('' !== $response->text) {
                        $trackedDelta($response->text);
                    }

                    return $response;
                } catch (AiException $e) {
                    if ($emittedAny) {
                        // Never stitch two different models' partial outputs together mid-stream
                        throw $e;
                    }
                    $errors[] = sprintf('%s (attempt %d): %s', $provider->name(), $attempt, SecretScrubber::scrub($e->getMessage()));
                    if ($attempt < $attempts && self::isTransient($e)) {
                        $this->sleepBackoff($attempt);
                        continue;
                    }
                    break;
                }
            }
        }

        throw new AiException('All configured AI providers failed during streaming: ' . implode(' | ', $errors));
    }

    /**
     * @param non-empty-list<string> $texts
     * @return list<list<float>>
     */
    public function embed(array $texts, ?string $model = null): array
    {
        $errors = [];

        // Embeddings from different providers live in incompatible vector spaces/dimensions,
        // so only retry the first EmbeddingProviderInterface instead of silently mixing vector spaces.
        $primaryEmbedder = null;
        foreach ($this->providers as $provider) {
            if ($provider instanceof EmbeddingProviderInterface) {
                $primaryEmbedder = $provider;
                break;
            }
        }

        if (null === $primaryEmbedder) {
            throw new AiException('No configured AI provider supports embeddings.');
        }

        $attempts = 1 + max(0, $this->maxRetriesPerProvider);
        for ($attempt = 1; $attempt <= $attempts; ++$attempt) {
            try {
                return $primaryEmbedder->embed($texts, $model);
            } catch (AiException $e) {
                $errors[] = sprintf('%s (attempt %d): %s', $primaryEmbedder->name(), $attempt, SecretScrubber::scrub($e->getMessage()));
                if ($attempt < $attempts && self::isTransient($e)) {
                    $this->sleepBackoff($attempt);
                    continue;
                }
                break;
            }
        }

        throw new AiException('Configured primary embedding provider failed (cross-provider embedding fallback is disabled to avoid mixing vector spaces): ' . implode(' | ', $errors));
    }

    public static function isTransient(AiException $e): bool
    {
        $msg = strtolower($e->getMessage());

        return 1 === preg_match('/\bhttp\s+(?:408|429|500|502|503|504|529)\b/', $msg)
            || str_contains($msg, 'rate limit')
            || str_contains($msg, 'too many requests')
            || str_contains($msg, 'timed out')
            || str_contains($msg, 'timeout')
            || str_contains($msg, 'temporarily unavailable')
            || str_contains($msg, 'overloaded')
            || str_contains($msg, 'connection refused')
            || str_contains($msg, 'could not resolve');
    }

    private function adaptRequestForFallback(ChatRequest $request, AiProviderInterface $fallback): ChatRequest
    {
        // Clear primary-specific model name so the fallback provider uses its own default model
        return new ChatRequest(
            messages: $request->messages,
            model: $fallback->defaultModel(),
            temperature: $request->temperature,
            maxTokens: $request->maxTokens,
            tools: $request->tools,
            jsonMode: $request->jsonMode,
        );
    }

    private function sleepBackoff(int $attempt): void
    {
        $delayMs = max(0, $this->baseBackoffMs) * (1 << max(0, $attempt - 1));
        if (0 === $delayMs) {
            return;
        }
        if (null !== $this->sleeper) {
            ($this->sleeper)($delayMs);

            return;
        }
        usleep($delayMs * 1000);
    }
}
