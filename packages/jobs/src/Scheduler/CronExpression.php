<?php

declare(strict_types=1);

namespace Kongono\Jobs\Scheduler;

use DateTimeInterface;
use Kongono\Jobs\JobException;

/**
 * Standard 5-field cron expression matcher (minute hour day-of-month month day-of-week).
 * Supports wildcards, step intervals, exact numbers, ranges, and comma-separated lists.
 */
final readonly class CronExpression
{
    /** @var list<string> */
    private array $fields;

    public function __construct(
        public string $expression,
    ) {
        $parts = preg_split('/\s+/', trim($expression));
        if (!is_array($parts) || 5 !== count($parts)) {
            throw new JobException(sprintf('Invalid 5-field cron expression "%s".', $expression));
        }
        $this->fields = $parts;

        // Validate all 5 fields eagerly so invalid cron expressions fail immediately on construction
        $bounds = [[0, 59], [0, 23], [1, 31], [1, 12], [0, 6]];
        foreach ($bounds as $idx => [$min, $max]) {
            $this->validateField($this->fields[$idx], $min, $max);
        }
    }

    public function isDue(DateTimeInterface $moment): bool
    {
        $minute = (int) $moment->format('i');
        $hour = (int) $moment->format('G');
        $dayOfMonth = (int) $moment->format('j');
        $month = (int) $moment->format('n');
        $dayOfWeek = (int) $moment->format('w'); // 0 (Sunday) - 6 (Saturday)

        if (
            !$this->matchesField($this->fields[0], $minute, 0, 59)
            || !$this->matchesField($this->fields[1], $hour, 0, 23)
            || !$this->matchesField($this->fields[3], $month, 1, 12)
        ) {
            return false;
        }

        // Standard POSIX cron semantics: when both day-of-month and day-of-week are restricted,
        // a date matches if EITHER matches; when only one is restricted, both must match.
        $domRestricted = '*' !== $this->fields[2];
        $dowRestricted = '*' !== $this->fields[4];
        $domMatch = $this->matchesField($this->fields[2], $dayOfMonth, 1, 31);
        $dowMatch = $this->matchesField($this->fields[4], $dayOfWeek, 0, 6);

        return ($domRestricted && $dowRestricted) ? ($domMatch || $dowMatch) : ($domMatch && $dowMatch);
    }

    private function validateField(string $field, int $min, int $max): void
    {
        foreach (explode(',', $field) as $segment) {
            $segment = trim($segment);
            if ('*' === $segment) {
                continue;
            }
            if (str_contains($segment, '/')) {
                [$rangePart, $stepStr] = explode('/', $segment, 2);
                if (!ctype_digit($stepStr) || (int) $stepStr < 1) {
                    throw new JobException(sprintf('Invalid cron step "%s" in "%s".', $stepStr, $this->expression));
                }
            } else {
                $rangePart = $segment;
            }
            if ('*' === $rangePart) {
                continue;
            }
            $effectiveMax = 6 === $max ? 7 : $max;
            if (str_contains($rangePart, '-')) {
                [$startStr, $endStr] = explode('-', $rangePart, 2);
                if (!ctype_digit($startStr) || !ctype_digit($endStr)) {
                    throw new JobException(sprintf('Invalid cron range "%s" in "%s".', $rangePart, $this->expression));
                }
                $start = (int) $startStr;
                $end = (int) $endStr;
                if ($start < $min || $end > $effectiveMax || $start > $end) {
                    throw new JobException(sprintf('Cron range "%s" out of bounds [%d..%d].', $rangePart, $min, $max));
                }
                continue;
            }
            if (ctype_digit($rangePart)) {
                $exact = (int) $rangePart;
                if ($exact < $min || $exact > $effectiveMax) {
                    throw new JobException(sprintf('Cron value %d out of bounds [%d..%d].', $exact, $min, $max));
                }
                continue;
            }
            throw new JobException(sprintf('Unsupported cron token "%s" in "%s".', $segment, $this->expression));
        }
    }

    private function matchesField(string $field, int $value, int $min, int $max): bool
    {
        foreach (explode(',', $field) as $segment) {
            $segment = trim($segment);
            if ('*' === $segment) {
                return true;
            }

            $step = 1;
            $hasStep = false;
            if (str_contains($segment, '/')) {
                [$rangePart, $stepStr] = explode('/', $segment, 2);
                if (!ctype_digit($stepStr) || (int) $stepStr < 1) {
                    throw new JobException(sprintf('Invalid cron step "%s" in "%s".', $stepStr, $this->expression));
                }
                $step = (int) $stepStr;
                $hasStep = true;
            } else {
                $rangePart = $segment;
            }

            if ('*' === $rangePart) {
                if (0 === ($value - $min) % $step) {
                    return true;
                }
                continue;
            }

            if (str_contains($rangePart, '-')) {
                [$startStr, $endStr] = explode('-', $rangePart, 2);
                if (!ctype_digit($startStr) || !ctype_digit($endStr)) {
                    throw new JobException(sprintf('Invalid cron range "%s" in "%s".', $rangePart, $this->expression));
                }
                $start = (int) $startStr;
                $end = (int) $endStr;
                $effectiveMax = 6 === $max ? 7 : $max;
                if ($start < $min || $end > $effectiveMax || $start > $end) {
                    throw new JobException(sprintf('Cron range "%s" out of bounds [%d..%d].', $rangePart, $min, $max));
                }
                if ($value >= $start && $value <= $end && 0 === ($value - $start) % $step) {
                    return true;
                }
                // Sunday (0) also satisfies ranges ending at 7 in day-of-week field (e.g. 5-7)
                if (6 === $max && 0 === $value && 7 >= $start && 7 <= $end && 0 === (7 - $start) % $step) {
                    return true;
                }
                continue;
            }

            if (ctype_digit($rangePart)) {
                $rawExact = (int) $rangePart;
                $effectiveMax = 6 === $max ? 7 : $max;
                if ($rawExact < $min || $rawExact > $effectiveMax) {
                    throw new JobException(sprintf('Cron value %d out of bounds [%d..%d].', $rawExact, $min, $max));
                }
                if ($hasStep) {
                    // For `7/step` in DOW field, 7 represents the top of [0..7], matching only Sunday (0)
                    if (6 === $max && 7 === $rawExact) {
                        if (0 === $value) {
                            return true;
                        }
                        continue;
                    }
                    if ($value >= $rawExact && $value <= $max && 0 === ($value - $rawExact) % $step) {
                        return true;
                    }
                    continue;
                }
                $exact = (6 === $max && 7 === $rawExact) ? 0 : $rawExact;
                if ($value === $exact) {
                    return true;
                }
                continue;
            }

            throw new JobException(sprintf('Unsupported cron token "%s" in "%s".', $segment, $this->expression));
        }

        return false;
    }
}
