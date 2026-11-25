<?php

declare(strict_types=1);

namespace Kongono\Jobs\Scheduler;

use Closure;
use DateTimeInterface;

/** Fluent scheduled task definition. */
final class ScheduledTask
{
    private CronExpression $cron;
    private bool $preventOverlap = false;
    private int $overlapTtlSeconds = 300;
    private bool $singleServer = false;
    private int $singleServerTtlSeconds = 60;

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
        if (1 !== preg_match('/\A(\d{1,2}):(\d{2})\z/', trim($hhmm), $m)) {
            throw new \Kongono\Jobs\JobException(sprintf('Invalid dailyAt() time "%s" (expected HH:MM).', $hhmm));
        }
        $hour = (int) $m[1];
        $minute = (int) $m[2];
        if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59) {
            throw new \Kongono\Jobs\JobException(sprintf('Out-of-range dailyAt() time "%s" (expected 00:00..23:59).', $hhmm));
        }

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

    /** Prevent concurrent executions of this task across workers using an atomic mutex lock. */
    public function withoutOverlapping(int $ttlSeconds = 300): self
    {
        $this->preventOverlap = true;
        $this->overlapTtlSeconds = max(1, $ttlSeconds);

        return $this;
    }

    /** Guarantee this task runs on at most one server per scheduled minute across a cluster. */
    public function onOneServer(int $ttlSeconds = 60): self
    {
        $this->singleServer = true;
        $this->singleServerTtlSeconds = max(60, $ttlSeconds);

        return $this;
    }

    public function preventsOverlap(): bool
    {
        return $this->preventOverlap;
    }

    public function overlapTtlSeconds(): int
    {
        return $this->overlapTtlSeconds;
    }

    public function runsOnOneServer(): bool
    {
        return $this->singleServer;
    }

    public function singleServerTtlSeconds(): int
    {
        return $this->singleServerTtlSeconds;
    }
}
