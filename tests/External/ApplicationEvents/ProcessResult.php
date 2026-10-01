<?php

declare(strict_types=1);

namespace Kongono\Tests\External\ApplicationEvents;

/** Detached evidence for one completely retired, bounded child process. */
final readonly class ProcessResult
{
    public function __construct(
        public int $exitCode,
        public string $stdoutPath,
        public string $stderrPath,
        public int $stdoutBytes,
        public int $stderrBytes,
        public string $stdoutSha256,
        public string $stderrSha256,
        public bool $timedOut,
        public bool $retired,
        public int $processId,
        public int $elapsedNanoseconds,
    ) {}

    public function stdout(): string
    {
        return (string) file_get_contents($this->stdoutPath);
    }

    public function stderr(): string
    {
        return (string) file_get_contents($this->stderrPath);
    }
}
