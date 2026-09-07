<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationRuntime\Tests\Unit\Execution;

use Kongono\ModelInvocationBuild\Plan\AdapterIdentity;
use Kongono\ModelInvocationRuntime\Tests\Fixture\FakeInvocationAdapter;
use Kongono\ModelInvocationRuntime\Tests\Fixture\FakeInvocationAdapterSession;
use Kongono\ModelInvocationRuntime\Tests\Fixture\FakeInvocationCancellation;
use Kongono\ModelInvocationRuntime\Tests\Fixture\FakeInvocationMonotonicClock;
use Kongono\ModelInvocationRuntime\Tests\Fixture\RuntimeFixture;
use PHPUnit\Framework\TestCase;

final class InvocationRuntimeRequestTest extends TestCase
{
    public function testRequestExposesOnlyExactCallerOwnedValues(): void
    {
        $source = RuntimeFixture::source();
        $adapter = new FakeInvocationAdapter(new AdapterIdentity('adapter.test'), new FakeInvocationAdapterSession([]));
        $cancellation = new FakeInvocationCancellation();
        $clock = new FakeInvocationMonotonicClock();
        $request = RuntimeFixture::request($source, $adapter, $cancellation, $clock);

        self::assertSame($source, $request->source());
        self::assertSame('receipt.test', $request->receiptIdentity()->value());
        self::assertSame($adapter, $request->adapter());
        self::assertSame($cancellation, $request->cancellation());
        self::assertSame($clock, $request->clock());
        self::assertNotSame('', $request->artifactBytes());
    }
}
