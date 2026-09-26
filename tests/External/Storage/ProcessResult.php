<?php

declare(strict_types=1);

namespace Kongono\Tests\External\Storage;

final readonly class ProcessResult
{
    public function __construct(
        public int $exitCode,
        public string $stdoutPath,
        public string $stderrPath,
        public int $stdoutBytes,
        public int $stderrBytes,
        public int $stdoutRawBytes,
        public int $stderrRawBytes,
        public int $stdoutOmittedBytes,
        public int $stderrOmittedBytes,
        public bool $stdoutOverflow,
        public bool $stderrOverflow,
        public bool $timedOut,
        public bool $retired,
        public int $processId,
    ) {}
}
