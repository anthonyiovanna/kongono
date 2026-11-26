<?php

declare(strict_types=1);

namespace Kongono\Ai;

use Closure;
use JsonException;
use Kongono\Ai\Provider\AiProviderInterface;
use Kongono\Ai\Provider\AnthropicProvider;
use Kongono\Ai\Provider\FallbackProvider;
use Kongono\Ai\Provider\GeminiProvider;
use Kongono\Ai\Provider\GroqProvider;
use Kongono\Ai\Provider\OllamaProvider;
use Kongono\Ai\Provider\OpenAiProvider;
use Kongono\Ai\Provider\StreamingAiProviderInterface;
use Kongono\Ai\Transport\HttpTransportInterface;
use Kongono\Core\Capabilities\ExecutionCapabilities;
use Kongono\Core\Config;
use Kongono\Validation\Validator;
use ReflectionClass;
use ReflectionNamedType;

/** Unified multi-provider AI client supporting OpenAI, Anthropic, Gemini, Groq, and Ollama. */
final readonly class AiClient
{
    public function __construct(
        private AiProviderInterface $provider,
    ) {}

    /**
     * Auto-configure an AiClient from environment variables:
     *   - KONGONO_AI_PROVIDER: openai | anthropic | gemini | groq | ollama (optional override)
     *   - KONGONO_AI_MODEL: model override (optional)
     *   - KONGONO_AI_FALLBACK_PROVIDERS: optional comma-separated ordered failover list (e.g. "openai,groq,ollama")
     *   - OPENAI_API_KEY (+ OPENAI_BASE_URL)
     *   - ANTHROPIC_API_KEY
     *   - GEMINI_API_KEY
     *   - GROQ_API_KEY (+ GROQ_BASE_URL)
     *   - OLLAMA_HOST (default http://127.0.0.1:11434)
     */
    public static function fromEnv(?HttpTransportInterface $transport = null): self
    {
        return self::fromEnvWithOverrides(
            providerOverride: Config::env('KONGONO_AI_PROVIDER'),
            modelOverride: Config::env('KONGONO_AI_MODEL'),
            transport: $transport,
        );
    }

    /**
     * Auto-configure an AiClient with per-companion provider/model routing and automatic multi-provider failover.
     *
     * Supported env vars for `$companion` (`oshie`, `chie`, or `hantei`):
     *   - `KONGONO_OSHIE_PROVIDER` / `KONGONO_OSHIE_MODEL`
     *   - `KONGONO_CHIE_PROVIDER`  / `KONGONO_CHIE_MODEL`
     *   - `KONGONO_HANTEI_PROVIDER` / `KONGONO_HANTEI_MODEL`
     *   - Falls back to `KONGONO_AI_PROVIDER` / `KONGONO_AI_MODEL` and any additional configured providers.
     */
    public static function fromEnvForCompanion(string $companion, ?HttpTransportInterface $transport = null): self
    {
        $prefix = 'KONGONO_' . strtoupper(trim($companion));
        $providerOverride = Config::env($prefix . '_PROVIDER') ?? Config::env('KONGONO_AI_PROVIDER');
        $modelOverride = Config::env($prefix . '_MODEL') ?? Config::env('KONGONO_AI_MODEL');

        return self::fromEnvWithOverrides(
            providerOverride: $providerOverride,
            modelOverride: $modelOverride,
            transport: $transport,
            autoDetectFallbacks: true,
        );
    }

    /** Create a resilient AiClient with ordered automatic provider failover and transient retries. */
    public static function withFallback(
        AiProviderInterface $primary,
        AiProviderInterface ...$fallbacks,
    ): self {
        if ([] === $fallbacks) {
            return new self($primary);
        }

        return new self(new FallbackProvider([$primary, ...array_values($fallbacks)]));
    }

    private static function fromEnvWithOverrides(
        ?string $providerOverride,
        ?string $modelOverride,
        ?HttpTransportInterface $transport,
        bool $autoDetectFallbacks = false,
    ): self {
        $selected = strtolower(trim($providerOverride ?? ''));
        $knownProviders = ['openai', 'anthropic', 'gemini', 'groq', 'ollama'];
        if ('' !== $selected && !in_array($selected, $knownProviders, true)) {
            throw new AiException(sprintf(
                'Unknown AI provider "%s". Supported providers: %s.',
                $selected,
                implode(', ', $knownProviders),
            ));
        }
        $cleanModel = null !== $modelOverride && '' !== trim($modelOverride) ? trim($modelOverride) : null;

        $order = [];
        if ('' !== $selected) {
            $order[] = $selected;
        }

        $fallbackCsv = Config::env('KONGONO_AI_FALLBACK_PROVIDERS');
        $hasExplicitFallbacks = null !== $fallbackCsv && '' !== trim($fallbackCsv);
        if ($hasExplicitFallbacks) {
            foreach (explode(',', $fallbackCsv) as $item) {
                $name = strtolower(trim($item));
                if ('' === $name) {
                    continue;
                }
                if (!in_array($name, $knownProviders, true)) {
                    throw new AiException(sprintf(
                        'Unknown fallback AI provider "%s". Supported providers: %s.',
                        $name,
                        implode(', ', $knownProviders),
                    ));
                }
                if (!in_array($name, $order, true)) {
                    $order[] = $name;
                }
            }
        } elseif ('' === $selected || ($autoDetectFallbacks && 'ollama' !== $selected)) {
            foreach ($knownProviders as $candidate) {
                if (!in_array($candidate, $order, true)) {
                    $order[] = $candidate;
                }
            }
        }

        /** @var list<AiProviderInterface> $providers */
        $providers = [];
        foreach ($order as $name) {
            $isPrimary = [] === $providers;
            $explicitlyRequested = $name === $selected;
            $provider = self::buildProviderFromEnv($name, $isPrimary ? $cleanModel : null, $transport, $explicitlyRequested);
            if (null === $provider) {
                continue;
            }
            $providers[] = $provider;

            if (!$autoDetectFallbacks && !$hasExplicitFallbacks) {
                break;
            }
        }

        if ([] === $providers) {
            throw new AiException(
                'No AI provider configured. Set OPENAI_API_KEY, ANTHROPIC_API_KEY, GEMINI_API_KEY, GROQ_API_KEY, or OLLAMA_HOST (e.g. http://127.0.0.1:11434).',
            );
        }

        if (1 === count($providers)) {
            return new self($providers[0]);
        }

        return new self(new FallbackProvider($providers));
    }

    private static function buildProviderFromEnv(
        string $name,
        ?string $modelOverride,
        ?HttpTransportInterface $transport,
        bool $allowWithoutKey,
    ): ?AiProviderInterface {
        if ('openai' === $name) {
            $key = Config::env('OPENAI_API_KEY');
            if (!$allowWithoutKey && (null === $key || '' === trim($key))) {
                return null;
            }
            $baseUrl = Config::env('OPENAI_BASE_URL') ?? 'https://api.openai.com/v1';

            return new OpenAiProvider($key ?? '', $modelOverride ?? 'gpt-4.1-mini', $baseUrl, $transport);
        }

        if ('anthropic' === $name) {
            $key = Config::env('ANTHROPIC_API_KEY');
            if (!$allowWithoutKey && (null === $key || '' === trim($key))) {
                return null;
            }

            return new AnthropicProvider($key ?? '', $modelOverride ?? 'claude-sonnet-4-20250514', transport: $transport);
        }

        if ('gemini' === $name) {
            $key = Config::env('GEMINI_API_KEY');
            if (!$allowWithoutKey && (null === $key || '' === trim($key))) {
                return null;
            }

            return new GeminiProvider($key ?? '', $modelOverride ?? 'gemini-2.5-flash', transport: $transport);
        }

        if ('groq' === $name) {
            $key = Config::env('GROQ_API_KEY');
            if (!$allowWithoutKey && (null === $key || '' === trim($key))) {
                return null;
            }
            $baseUrl = Config::env('GROQ_BASE_URL') ?? GroqProvider::DEFAULT_BASE_URL;

            return new GroqProvider($key ?? '', $modelOverride ?? GroqProvider::DEFAULT_MODEL, $baseUrl, $transport);
        }

        if ('ollama' === $name) {
            $host = Config::env('OLLAMA_HOST');
            if (!$allowWithoutKey && (null === $host || '' === trim($host))) {
                return null;
            }

            return new OllamaProvider($modelOverride ?? 'qwen2.5-coder:7b', $host ?? 'http://127.0.0.1:11434', $transport);
        }

        return null;
    }

    public function providerName(): string
    {
        return $this->provider->name();
    }

    public function defaultModel(): string
    {
        return $this->provider->defaultModel();
    }

    public function chat(ChatRequest $request): ChatResponse
    {
        $caps = ExecutionCapabilities::current();
        if (null !== $caps) {
            if ($caps->noOutboundHttp) {
                $caps->assertOutboundHttpAllowed('ai://' . $this->provider->name());
            }
            if (null !== $caps->maxAiCostUsd) {
                $caps->recordAiSpend(0.0);
            }
        }

        $response = $this->provider->chat($request);

        if (null !== $caps && null !== $caps->maxAiCostUsd) {
            $caps->recordAiSpend(self::estimateCostUsd(
                $response->provider,
                $response->model,
                self::effectiveUsageForSpend($request, $response),
            ));
        }

        return $response;
    }

    /**
     * Stream token deltas to $onDelta as they arrive (falls back to single-chunk delivery if provider does not stream).
     *
     * @param Closure(string): void $onDelta
     */
    public function chatStream(ChatRequest $request, Closure $onDelta): ChatResponse
    {
        $caps = ExecutionCapabilities::current();
        if (null !== $caps) {
            if ($caps->noOutboundHttp) {
                $caps->assertOutboundHttpAllowed('ai://' . $this->provider->name());
            }
            if (null !== $caps->maxAiCostUsd) {
                $caps->recordAiSpend(0.0);
            }
        }

        if ($this->provider instanceof StreamingAiProviderInterface) {
            $response = $this->provider->chatStream($request, $onDelta);
        } else {
            $response = $this->provider->chat($request);
            if ('' !== $response->text) {
                $onDelta($response->text);
            }
        }

        if (null !== $caps && null !== $caps->maxAiCostUsd) {
            $caps->recordAiSpend(self::estimateCostUsd(
                $response->provider,
                $response->model,
                self::effectiveUsageForSpend($request, $response),
            ));
        }

        return $response;
    }

    /**
     * Fall back to a conservative byte-based token estimate (~4 bytes/token) when a provider
     * (especially SSE streaming endpoints) omits usage metadata, preventing #[MaxAiCost] bypasses.
     */
    private static function effectiveUsageForSpend(ChatRequest $request, ChatResponse $response): TokenUsage
    {
        if ($response->usage->inputTokens > 0 || $response->usage->outputTokens > 0) {
            return $response->usage;
        }
        $inputBytes = 0;
        foreach ($request->messages as $msg) {
            $inputBytes += strlen($msg->content);
            foreach ($msg->attachments as $attachment) {
                // Conservative image/document token accounting (~1024 tokens floor or raw bytes / 32)
                $inputBytes += max(4096, intdiv($attachment->byteSize, 8));
            }
        }
        $outputBytes = strlen($response->text);
        if (0 === $inputBytes && 0 === $outputBytes) {
            return $response->usage;
        }

        return new TokenUsage(
            inputTokens: $inputBytes > 0 ? max(1, (int) ceil($inputBytes / 4)) : 0,
            outputTokens: $outputBytes > 0 ? max(1, (int) ceil($outputBytes / 4)) : 0,
        );
    }

    /** Conservative model-aware USD cost estimate used for `#[MaxAiCost]` capability enforcement. */
    public static function estimateCostUsd(string $provider, string $model, TokenUsage $usage): float
    {
        if ('ollama' === strtolower($provider)) {
            return 0.0;
        }
        $lower = strtolower($model);
        [$inPer1k, $outPer1k] = match (true) {
            str_contains($lower, 'opus') => [0.015, 0.075],
            str_contains($lower, 'sonnet') => [0.003, 0.015],
            str_contains($lower, 'haiku') => [0.0008, 0.004],
            str_contains($lower, 'gpt-4.1-mini'), str_contains($lower, 'gpt-4o-mini') => [0.0004, 0.0016],
            str_contains($lower, 'gpt-4.1'), str_contains($lower, 'gpt-4o') => [0.0025, 0.010],
            str_contains($lower, 'gemini-2.5-pro'), str_contains($lower, 'gemini-1.5-pro') => [0.00125, 0.010],
            str_contains($lower, 'gemini') => [0.0003, 0.0025],
            'groq' === strtolower($provider) => [0.0006, 0.0008],
            default => [0.005, 0.015],
        };

        return (($usage->inputTokens / 1000.0) * $inPer1k) + (($usage->outputTokens / 1000.0) * $outPer1k);
    }

    /**
     * Convenience one-shot helper for simple text or multimodal prompts.
     *
     * @param list<Attachment> $attachments Optional verified image/PDF attachments
     */
    public function ask(
        string $prompt,
        ?string $system = null,
        ?float $temperature = null,
        bool $jsonMode = false,
        array $attachments = [],
    ): string {
        $messages = [];
        if (null !== $system && '' !== trim($system)) {
            $messages[] = Message::system($system);
        }
        $messages[] = Message::user($prompt, $attachments);

        return $this->chat(new ChatRequest($messages, temperature: $temperature, jsonMode: $jsonMode))->text;
    }

    /**
     * Request structured JSON output from the LLM (text, image OCR, or PDF) and validate/hydrate it directly into a PHP 8.5 readonly DTO.
     *
     * @template T of object
     * @param class-string<T>  $dtoClass
     * @param list<Attachment> $attachments Optional verified image/PDF attachments for Receipt OCR or photo triage
     * @return T
     */
    public function structured(
        string $prompt,
        string $dtoClass,
        ?string $system = null,
        ?float $temperature = 0.1,
        array $attachments = [],
    ): object {
        $shape = $this->describeDtoFields($dtoClass);
        $systemInstruction = trim(($system ?? '') . "\n\nRespond ONLY with a valid JSON object matching these exact fields:\n" . $shape);

        $raw = trim($this->ask($prompt, $systemInstruction, $temperature, jsonMode: true, attachments: $attachments));
        if (str_starts_with($raw, '```')) {
            $stripped = preg_replace('/\A```(?:json)?\s*|\s*```\z/', '', $raw);
            if (is_string($stripped)) {
                $raw = trim($stripped);
            }
        }

        try {
            $decoded = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new AiException('Structured AI response was not valid JSON.', previous: $e);
        }

        if (!is_array($decoded)) {
            throw new AiException('Structured AI response must decode to a JSON object.');
        }

        /** @var array<string, mixed> $input */
        $input = [];
        foreach ($decoded as $k => $v) {
            $input[(string) $k] = $v;
        }

        return (new Validator())->validate($dtoClass, $input);
    }

    /** @param class-string $dtoClass */
    private function describeDtoFields(string $dtoClass): string
    {
        $ref = new ReflectionClass($dtoClass);
        $ctor = $ref->getConstructor();
        if (null === $ctor) {
            return '{}';
        }
        $fields = [];
        foreach ($ctor->getParameters() as $param) {
            $type = $param->getType();
            $typeLabel = $type instanceof ReflectionNamedType ? $type->getName() : 'mixed';
            $optional = $param->isDefaultValueAvailable() || $param->allowsNull() ? ' (optional)' : ' (required)';
            $fields[] = sprintf('- "%s": %s%s', $param->getName(), $typeLabel, $optional);
        }

        return implode("\n", $fields);
    }
}
