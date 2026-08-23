<?php

declare(strict_types=1);

namespace Kongono\Hantei\Tests\Unit\Internal;

use Kongono\Hantei\Internal\ProcessRunner;
use PHPUnit\Framework\TestCase;

final class ProcessRunnerTest extends TestCase
{
    public function testArgumentVectorCapturesSeparateBoundedStreams(): void
    {
        $result = (new ProcessRunner())->run([PHP_BINARY, '-r', 'fwrite(STDOUT, "out"); fwrite(STDERR, "err");'], __DIR__, 5, 1024);

        self::assertTrue($result->started());
        self::assertSame(0, $result->exitCode());
        self::assertSame('out', $result->stdout());
        self::assertSame('err', $result->stderr());
        self::assertNull($result->blockedReason());
    }

    public function testOutputOverflowBlocksWithoutExceedingLimit(): void
    {
        $result = (new ProcessRunner())->run([PHP_BINARY, '-r', 'fwrite(STDOUT, str_repeat("x", 2048));'], __DIR__, 5, 128);

        self::assertSame('output-limit', $result->blockedReason());
        self::assertSame(128, strlen($result->stdout()));
    }

    public function testTimeoutBlocks(): void
    {
        $result = (new ProcessRunner())->run([PHP_BINARY, '-r', 'usleep(2000000);'], __DIR__, 1, 1024);

        self::assertSame('process-timeout', $result->blockedReason());
    }
}
