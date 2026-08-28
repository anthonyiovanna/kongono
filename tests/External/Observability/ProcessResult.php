<?php

declare(strict_types=1);

namespace Kongono\Tests\External\Observability;

/** Captures one bounded child-process observation without interpreting its bytes. */
final readonly class ProcessResult
{
    public function __construct(
        private bool $started,
        private ?int $exitCode,
        private string $stdout,
        private string $stderr,
        private ?string $blockedReason,
    ) {}

    public function started(): bool
    {
        return $this->started;
    }

    public function exitCode(): ?int
    {
        return $this->exitCode;
    }

    public function stdout(): string
    {
        return $this->stdout;
    }

    public function stderr(): string
    {
        return $this->stderr;
    }

    public function blockedReason(): ?string
    {
        return $this->blockedReason;
    }
}
