<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationRuntime\Tests\Unit\Adapter;

use Kongono\ModelInvocationContract\Stream\StreamEventKind;
use Kongono\ModelInvocationRuntime\Adapter\InvocationAdapterEmission;
use Kongono\ModelInvocationRuntime\Tests\Fixture\RuntimeFixture;
use PHPUnit\Framework\TestCase;

final class InvocationAdapterEmissionTest extends TestCase
{
    public function testNamedConstructorsCarryExactlyOneNormalizedValue(): void
    {
        $source = RuntimeFixture::source(streaming: true);
        $event = RuntimeFixture::event($source, 1, StreamEventKind::Started)->eventValue();
        self::assertNotNull($event);
        $eventEmission = InvocationAdapterEmission::event($event);
        self::assertSame($event, $eventEmission->eventValue());
        self::assertNull($eventEmission->resultValue());

        $result = RuntimeFixture::result($source);
        $resultEmission = InvocationAdapterEmission::result($result);
        self::assertSame($result, $resultEmission->resultValue());
        self::assertNull($resultEmission->eventValue());
    }
}
