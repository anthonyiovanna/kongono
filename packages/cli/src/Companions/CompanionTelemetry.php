<?php

declare(strict_types=1);

namespace Kongono\Cli\Companions;

use Kongono\Ai\AiClient;
use Kongono\Ai\TokenUsage;
use Kongono\Core\Config;
use Kongono\Core\CoreException;

/**
 * Immutable token, latency, and USD cost telemetry for Oshie, Chie, and Hantei invocations.
 * Supports hard per-invocation cost caps via `KONGONO_AI_MAX_COST_USD`.
 */
final readonly class CompanionTelemetry
{
    public function __construct(
        public string $provider,
        public string $model,
        public int $inputTokens,
        public int $outputTokens,
        public float $durationMs,
        public float $estimatedCostUsd,
        public float $maxCostUsd = 0.0,
    ) {}

    /** Read optional hard USD budget cap from `KONGONO_AI_MAX_COST_USD` (0.0 = unlimited). */
    public static function maxCostFromEnv(): float
    {
        $raw = Config::env('KONGONO_AI_MAX_COST_USD');
        if (null === $raw || '' === trim($raw) || !is_numeric($raw)) {
            return 0.0;
        }
        $val = (float) $raw;

        return $val > 0.0 ? $val : 0.0;
    }

    public static function fromUsage(
        string $provider,
        string $model,
        TokenUsage $usage,
        float $durationMs,
        ?float $maxCostUsd = null,
    ): self {
        $cap = $maxCostUsd ?? self::maxCostFromEnv();
        $cost = self::estimateCostUsd($provider, $model, $usage->inputTokens, $usage->outputTokens);

        return new self(
            provider: $provider,
            model: $model,
            inputTokens: $usage->inputTokens,
            outputTokens: $usage->outputTokens,
            durationMs: round(max(0.0, $durationMs), 2),
            estimatedCostUsd: $cost,
            maxCostUsd: max(0.0, $cap),
        );
    }

    /** Estimate USD cost via canonical AiClient model pricing (Ollama = $0.00). */
    public static function estimateCostUsd(string $provider, string $model, int $inputTokens, int $outputTokens): float
    {
        $cost = AiClient::estimateCostUsd(
            $provider,
            $model,
            new TokenUsage(max(0, $inputTokens), max(0, $outputTokens)),
        );

        return round(max(0.0, $cost), 6);
    }

    public function totalTokens(): int
    {
        return $this->inputTokens + $this->outputTokens;
    }

    public function exceedsBudget(): bool
    {
        return $this->maxCostUsd > 0.0 && $this->estimatedCostUsd > $this->maxCostUsd;
    }

    public function assertWithinBudget(string $companion): void
    {
        if ($this->exceedsBudget()) {
            throw new CoreException(sprintf(
                '%s exceeded configured cost budget ($%.6f > $%.6f). Raise KONGONO_AI_MAX_COST_USD to allow larger runs.',
                $companion,
                $this->estimatedCostUsd,
                $this->maxCostUsd,
            ));
        }
    }

    public function merge(self $other): self
    {
        return new self(
            provider: $other->provider,
            model: $other->model,
            inputTokens: $this->inputTokens + $other->inputTokens,
            outputTokens: $this->outputTokens + $other->outputTokens,
            durationMs: round($this->durationMs + $other->durationMs, 2),
            estimatedCostUsd: round($this->estimatedCostUsd + $other->estimatedCostUsd, 6),
            maxCostUsd: $this->maxCostUsd > 0.0 ? $this->maxCostUsd : $other->maxCostUsd,
        );
    }

    public function formatFooter(): string
    {
        $costLabel = 'ollama' === strtolower($this->provider)
            ? '$0.00 (local)'
            : sprintf('~$%.5f', $this->estimatedCostUsd);
        $budget = $this->maxCostUsd > 0.0 ? sprintf(' / cap $%.4f', $this->maxCostUsd) : '';

        return sprintf(
            '[%s/%s · %d tok (%d in / %d out) · %.1fms · %s%s]',
            $this->provider,
            $this->model,
            $this->totalTokens(),
            $this->inputTokens,
            $this->outputTokens,
            $this->durationMs,
            $costLabel,
            $budget,
        );
    }

    /**
     * @return array{
     *     provider: string,
     *     model: string,
     *     input_tokens: int,
     *     output_tokens: int,
     *     total_tokens: int,
     *     duration_ms: float,
     *     estimated_cost_usd: float,
     *     max_cost_usd: float,
     *     footer: string
     * }
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'model' => $this->model,
            'input_tokens' => $this->inputTokens,
            'output_tokens' => $this->outputTokens,
            'total_tokens' => $this->totalTokens(),
            'duration_ms' => $this->durationMs,
            'estimated_cost_usd' => $this->estimatedCostUsd,
            'max_cost_usd' => $this->maxCostUsd,
            'footer' => $this->formatFooter(),
        ];
    }
}
