<?php

declare(strict_types=1);

namespace Kongono\AssetAdapterVite\Tests\Integration;

use Kongono\AssetAdapterContract\Process\BuildProcessCommand;
use Kongono\AssetAdapterContract\Process\BuildProcessResult;
use Kongono\AssetAdapterVite\Process\SystemBuildProcessRunner;
use PHPUnit\Framework\TestCase;

final class SystemProcessLifecycleTest extends TestCase
{
    public function testRunnerRetainsNoStateAcrossSequentialAndFiberCalls(): void
    {
        $runner = new SystemBuildProcessRunner();
        $make = static fn(string $text) => new BuildProcessCommand([str_replace('\\', '/', PHP_BINARY),'-r','echo ' . var_export($text, true) . ';'], str_replace('\\', '/', sys_get_temp_dir()), [], 5, 100, 100);
        $first = $runner->run($make('first'));
        $fiber = new \Fiber(static fn() => $runner->run($make('second')));
        $fiber->start();
        $second = $fiber->getReturn();
        self::assertInstanceOf(BuildProcessResult::class, $second);
        self::assertSame('first', $first->stdout());
        self::assertSame('second', $second->stdout());
        self::assertTrue($first->succeeded() && $second->succeeded());
    }
}
