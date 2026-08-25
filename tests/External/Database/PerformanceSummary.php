<?php

declare(strict_types=1);

namespace Kongono\Tests\External\Database;

use InvalidArgumentException;

/** Reduces one fixed measurement sample set without retaining raw timings. */
final readonly class PerformanceSummary
{
    private function __construct(
        private int $samples,
        private int $minimum,
        private int $median,
        private int $ninetyFifthPercentile,
        private int $maximum,
    ) {}

    /** @param list<mixed> $samples */
    public static function fromSamples(array $samples): self
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

        return new self(
            count($validated),
            $validated[0],
            self::nearestRank($validated, 50),
            self::nearestRank($validated, 95),
            $validated[array_key_last($validated)],
        );
    }

    /** @return array{samples: int, min_ns: int, p50_ns: int, p95_ns: int, max_ns: int} */
    public function toArray(): array
    {
        return [
            'samples' => $this->samples,
            'min_ns' => $this->minimum,
            'p50_ns' => $this->median,
            'p95_ns' => $this->ninetyFifthPercentile,
            'max_ns' => $this->maximum,
        ];
    }

    /** @param non-empty-list<int> $samples */
    private static function nearestRank(array $samples, int $percentile): int
    {
        $rank = (int) ceil(($percentile / 100) * count($samples));

        return $samples[$rank - 1];
    }
}
