<?php

declare(strict_types=1);

namespace Kongono\Tests\External\Persistence;

use InvalidArgumentException;
use RuntimeException;

/** Runs and reduces the fixed Phase 7 local observational timing protocol. */
final class PersistencePerformance
{
    public const WARMUP_ITERATIONS = 10;
    public const MEASURED_SAMPLES = 50;

    /**
     * @param callable(): mixed $operation
     * @param callable(mixed): bool $verify
     * @return array{samples:int,min_ns:int,p50_ns:int,p95_ns:int,max_ns:int}
     */
    public function measure(callable $operation, callable $verify): array
    {
        for ($iteration = 0; $iteration < self::WARMUP_ITERATIONS; ++$iteration) {
            if (!$verify($operation())) {
                throw new RuntimeException('performance_result_invalid');
            }
        }

        $samples = [];
        for ($iteration = 0; $iteration < self::MEASURED_SAMPLES; ++$iteration) {
            $started = hrtime(true);
            $result = $operation();
            $elapsed = hrtime(true) - $started;
            if ($elapsed <= 0 || !$verify($result)) {
                throw new RuntimeException('performance_measurement_invalid');
            }
            $samples[] = $elapsed;
        }

        return self::summarize($samples);
    }

    /**
     * @param list<mixed> $samples
     * @return array{samples:int,min_ns:int,p50_ns:int,p95_ns:int,max_ns:int}
     */
    public static function summarize(array $samples): array
    {
        if ([] === $samples) {
            throw new InvalidArgumentException('performance_samples_empty');
        }
        $validated = [];
        foreach ($samples as $sample) {
            if (!is_int($sample) || $sample <= 0) {
                throw new InvalidArgumentException('performance_sample_invalid');
            }
            $validated[] = $sample;
        }
        sort($validated, SORT_NUMERIC);

        return [
            'samples' => count($validated),
            'min_ns' => $validated[0],
            'p50_ns' => self::nearestRank($validated, 50),
            'p95_ns' => self::nearestRank($validated, 95),
            'max_ns' => $validated[array_key_last($validated)],
        ];
    }

    /** @param non-empty-list<int> $samples */
    private static function nearestRank(array $samples, int $percentile): int
    {
        return $samples[(int) ceil(($percentile / 100) * count($samples)) - 1];
    }
}
