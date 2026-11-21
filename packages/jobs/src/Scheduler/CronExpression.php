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
    }

    public function isDue(DateTimeInterface $moment): bool
    {
        $minute = (int) $moment->format('i');
        $hour = (int) $moment->format('G');
        $dayOfMonth = (int) $moment->format('j');
        $month = (int) $moment->format('n');
        $dayOfWeek = (int) $moment->format('w'); // 0 (Sunday) - 6 (Saturday)

        return $this->matchesField($this->fields[0], $minute, 0, 59)
            && $this->matchesField($this->fields[1], $hour, 0, 23)
            && $this->matchesField($this->fields[2], $dayOfMonth, 1, 31)
            && $this->matchesField($this->fields[3], $month, 1, 12)
            && $this->matchesField($this->fields[4], $dayOfWeek, 0, 6);
    }

    private function matchesField(string $field, int $value, int $min, int $max): bool
    {
        foreach (explode(',', $field) as $segment) {
            $segment = trim($segment);
            if ('*' === $segment) {
                return true;
            }

            $step = 1;
            if (str_contains($segment, '/')) {
                [$rangePart, $stepStr] = explode('/', $segment, 2);
                if (!ctype_digit($stepStr) || (int) $stepStr < 1) {
                    throw new JobException(sprintf('Invalid cron step "%s" in "%s".', $stepStr, $this->expression));
                }
                $step = (int) $stepStr;
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
                if ($value >= $start && $value <= $end && 0 === ($value - $start) % $step) {
                    return true;
                }
                continue;
            }

            if (ctype_digit($rangePart)) {
                $exact = (int) $rangePart;
                // Allow Sunday as both 0 and 7 in day-of-week field
                if (6 === $max && 7 === $exact) {
                    $exact = 0;
                }
                if ($exact < $min || $exact > $max) {
                    throw new JobException(sprintf('Cron value %d out of bounds [%d..%d].', $exact, $min, $max));
                }
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
