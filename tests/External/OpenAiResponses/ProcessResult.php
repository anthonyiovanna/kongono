<?php

declare(strict_types=1);

namespace Kongono\Tests\External\OpenAiResponses;

final readonly class ProcessResult
{
    public function __construct(public int $exitCode, public string $stdout, public string $stderr) {}
}
