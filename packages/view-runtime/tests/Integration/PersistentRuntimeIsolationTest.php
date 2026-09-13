<?php

declare(strict_types=1);

namespace Kongono\ViewRuntime\Tests\Integration;

use Fiber;
use Kongono\ViewContract\Render\RenderLimits;
use Kongono\ViewRuntime\Internal\BufferedRenderSink;
use PHPUnit\Framework\TestCase;

final class PersistentRuntimeIsolationTest extends TestCase
{
    public function testSequentialAndCooperativelyInterleavedOperationStateIsIndependent(): void
    {
        $left = new BufferedRenderSink(new RenderLimits(16, 2));
        $right = new BufferedRenderSink(new RenderLimits(16, 2));
        $leftFiber = new Fiber(static function () use ($left): string {
            $left->begin();
            $left->step(0);
            $left->literal('left-', 0);
            Fiber::suspend();
            $left->step(1);
            $left->literal('one', 1);
            [$output] = $left->complete();
            $bytes = $output->bytes();
            $left->retire();

            return $bytes;
        });
        $rightFiber = new Fiber(static function () use ($right): string {
            $right->begin();
            $right->step(0);
            $right->literal('right-', 0);
            Fiber::suspend();
            $right->step(1);
            $right->literal('two', 1);
            [$output] = $right->complete();
            $bytes = $output->bytes();
            $right->retire();

            return $bytes;
        });
        $leftFiber->start();
        $rightFiber->start();
        $rightFiber->resume();
        $leftFiber->resume();
        self::assertSame('left-one', $leftFiber->getReturn());
        self::assertSame('right-two', $rightFiber->getReturn());
        self::assertSame(0, $left->outputByteCount());
        self::assertSame(0, $right->outputByteCount());
    }
}
