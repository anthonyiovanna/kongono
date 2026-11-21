<?php

declare(strict_types=1);

namespace Kongono\Jobs\Scheduler;

use Closure;
use DateTimeInterface;

/** Fluent scheduled task definition. */
final class ScheduledTask
{
    private CronExpression $cron;

    /** @param Closure(): void $callback */
    public function __construct(
        public readonly string $name,
        public readonly Closure $callback,
        string $expression = '* * * * *',
    ) {
        $this->cron = new CronExpression($expression);
    }

    public function cron(string $expression): self
    {
        $this->cron = new CronExpression($expression);

        return $this;
    }

    public function everyMinute(): self
    {
        return $this->cron('* * * * *');
    }

    public function everyFiveMinutes(): self
    {
        return $this->cron('*/5 * * * *');
    }

    public function everyFifteenMinutes(): self
    {
        return $this->cron('*/15 * * * *');
    }

    public function hourly(): self
    {
        return $this->cron('0 * * * *');
    }

    public function daily(): self
    {
        return $this->cron('0 0 * * *');
    }

    public function dailyAt(string $hhmm): self
    {
        $parts = explode(':', $hhmm);
        $hour = ctype_digit($parts[0]) ? (int) $parts[0] : 0;
        $minute = isset($parts[1]) && ctype_digit($parts[1]) ? (int) $parts[1] : 0;

        return $this->cron(sprintf('%d %d * * *', $minute, $hour));
    }

    public function weekly(): self
    {
        return $this->cron('0 0 * * 0');
    }

    public function expression(): string
    {
        return $this->cron->expression;
    }

    public function isDue(DateTimeInterface $moment): bool
    {
        return $this->cron->isDue($moment);
    }
}
