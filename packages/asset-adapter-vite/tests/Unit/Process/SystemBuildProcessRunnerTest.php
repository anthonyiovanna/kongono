<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Tests\Unit\Process;

use Kongono\AssetAdapterContract\Process\BuildProcessCommand;
use Kongono\AssetAdapterVite\Process\SystemBuildProcessRunner;
use PHPUnit\Framework\TestCase;

final class SystemBuildProcessRunnerTest extends TestCase
{
    public function testDirectPhpProcessCapturesBothStreamsAndReaps(): void
    {
        $command = new BuildProcessCommand([str_replace('\\', '/', PHP_BINARY),'-r','fwrite(STDOUT,"out"); fwrite(STDERR,"err");'], str_replace('\\', '/', sys_get_temp_dir()), [], 5, 10, 10);
        $result = (new SystemBuildProcessRunner())->run($command);
        self::assertTrue($result->succeeded());
        self::assertSame('out', $result->stdout());
        self::assertSame('err', $result->stderr());
        self::assertTrue($result->streamsClosed());
        self::assertTrue($result->processClosed());
    }public function testTimeoutTerminatesDirectHandle(): void
    {
        $command = new BuildProcessCommand([str_replace('\\', '/', PHP_BINARY),'-r','sleep(2);'], str_replace('\\', '/', sys_get_temp_dir()), [], 1, 10, 10);
        $result = (new SystemBuildProcessRunner())->run($command);
        self::assertTrue($result->timedOut());
        self::assertSame('timeout', $result->failureReason());
        self::assertTrue($result->processClosed());
    }
}
