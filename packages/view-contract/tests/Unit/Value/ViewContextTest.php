<?php

declare(strict_types=1);

namespace Kongono\ViewContract\Tests\Unit\Value;

use Kongono\ViewContract\Exception\InvalidViewContractException;
use Kongono\ViewContract\Value\ListValue;
use Kongono\ViewContract\Value\StringValue;
use Kongono\ViewContract\Value\ViewContext;
use PHPUnit\Framework\TestCase;

final class ViewContextTest extends TestCase
{
    public function testContextCanonicalizesDetachesAndCompares(): void
    {
        $context = new ViewContext(['z' => new StringValue('z'), 'a' => new StringValue('a')]);
        self::assertSame(['a', 'z'], array_keys($context->fields()));
        self::assertTrue($context->equals(new ViewContext(['a' => new StringValue('a'), 'z' => new StringValue('z')])));
        self::assertSame(2, $context->depth());
        self::assertSame(3, $context->nodeCount());
    }

    public function testDepthBoundIncludesRootContext(): void
    {
        $value = new StringValue('leaf');
        for ($depth = 2; $depth <= 15; ++$depth) {
            $value = new ListValue([$value]);
        }
        self::assertSame(15, $value->depth());
        self::assertSame(16, (new ViewContext(['value' => $value]))->depth());

        $this->expectException(InvalidViewContractException::class);
        new ViewContext(['value' => new ListValue([$value])]);
    }

    public function testAggregateNodeBoundIsExact(): void
    {
        $groups = array_fill(0, 255, new ListValue(array_fill(0, 15, new StringValue('x'))));
        $groups[] = new ListValue(array_fill(0, 13, new StringValue('x')));
        self::assertSame(4096, (new ViewContext(['value' => new ListValue($groups)]))->nodeCount());

        $groups[255] = new ListValue(array_fill(0, 14, new StringValue('x')));
        $this->expectException(InvalidViewContractException::class);
        new ViewContext(['value' => new ListValue($groups)]);
    }
}
