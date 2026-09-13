<?php

declare(strict_types=1);

namespace Kongono\ViewRuntime\Tests\Unit\Internal;

use Kongono\ViewContract\Output\OutputContext;
use Kongono\ViewContract\Render\RenderLimits;
use Kongono\ViewContract\Value\IntegerValue;
use Kongono\ViewContract\Value\StringValue;
use Kongono\ViewRuntime\Exception\RenderBoundExceededException;
use Kongono\ViewRuntime\Exception\ViewRenderingException;
use Kongono\ViewRuntime\Internal\BufferedRenderSink;
use PHPUnit\Framework\TestCase;

final class BufferedRenderSinkTest extends TestCase
{
    public function testCompletesAtExactByteAndStepLimits(): void
    {
        $sink = new BufferedRenderSink(new RenderLimits(6, 2));
        $sink->begin();
        $sink->step(0);
        $sink->literal('ab', 0);
        $sink->step(0);
        $sink->value(new StringValue('<'), OutputContext::HtmlText, 0);
        [$output, $metadata] = $sink->complete();
        self::assertSame('ab&lt;', $output->bytes());
        self::assertSame(2, $metadata->renderStepCount());
        self::assertSame(1, $metadata->escapedValueCount());
    }

    public function testRejectsStepBeforeExceedingLimitAndClearsOnRetirement(): void
    {
        $sink = new BufferedRenderSink(new RenderLimits(8, 1));
        $sink->begin();
        $sink->step(4);
        $sink->literal('x', 4);
        try {
            $sink->step(4);
            self::fail('Expected step bound rejection.');
        } catch (RenderBoundExceededException $failure) {
            self::assertSame('KGN-VIEW-RUNTIME-1835', $failure->diagnosticId());
        } finally {
            $sink->retire();
        }
        self::assertSame(0, $sink->outputByteCount());
        self::assertSame(0, $sink->renderStepCount());
    }

    public function testRejectsByteOverflowProtocolViolationsAndNonStringValues(): void
    {
        $sink = new BufferedRenderSink(new RenderLimits(1, 3));
        $sink->begin();
        $sink->step(0);
        try {
            $sink->literal('xx', 0);
            self::fail('Expected byte bound rejection.');
        } catch (RenderBoundExceededException) {
            self::addToAssertionCount(1);
        }
        $sink->retire();

        $sink = new BufferedRenderSink(new RenderLimits(8, 3));
        $sink->begin();
        $sink->step(0);
        $this->expectException(ViewRenderingException::class);
        $sink->value(new IntegerValue(1), OutputContext::HtmlText, 0);
    }
}
