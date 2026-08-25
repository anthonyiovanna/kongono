<?php

declare(strict_types=1);

namespace Kongono\Tests\External\Database;

use RuntimeException;

/** Runs the fixed Phase 6 local observational timing protocol. */
final class PerformanceMeasurement
{
    public const WARMUP_ITERATIONS = 10;
    public const MEASURED_SAMPLES = 50;

    /**
     * @param callable(): mixed $operation
     * @param callable(mixed): bool $verify
     */
    public function measure(callable $operation, callable $verify): PerformanceSummary
    {
        for ($iteration = 0; $iteration < self::WARMUP_ITERATIONS; ++$iteration) {
            $this->execute($operation, $verify);
        }

        $samples = [];
        for ($iteration = 0; $iteration < self::MEASURED_SAMPLES; ++$iteration) {
            $started = hrtime(true);
            $result = $operation();
            $elapsed = hrtime(true) - $started;
            if ($elapsed <= 0) {
                throw new RuntimeException('performance_duration_invalid');
            }
            if (!$verify($result)) {
                throw new RuntimeException('performance_result_invalid');
            }
            $samples[] = $elapsed;
        }

        return PerformanceSummary::fromSamples($samples);
    }

    /**
     * @param callable(): mixed $operation
     * @param callable(mixed): bool $verify
     */
    private function execute(callable $operation, callable $verify): void
    {
        if (!$verify($operation())) {
            throw new RuntimeException('performance_result_invalid');
        }
    }
}
