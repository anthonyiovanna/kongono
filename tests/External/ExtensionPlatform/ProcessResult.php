<?php

declare(strict_types=1);

namespace Kongono\Tests\External\ExtensionPlatform;

final readonly class ProcessResult
{
    public function __construct(
        public bool $started,
        public int $exitCode,
        public string $stdout,
        public string $stderr,
        public ?string $blockedReason = null,
    ) {}
}
