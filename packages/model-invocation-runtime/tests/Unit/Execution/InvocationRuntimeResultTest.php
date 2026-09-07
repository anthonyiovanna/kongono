<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationRuntime\Tests\Unit\Execution;

use Kongono\ModelInvocationBuild\Plan\AdapterIdentity;
use Kongono\ModelInvocationContract\Lifecycle\InvocationState;
use Kongono\ModelInvocationContract\Receipt\InvocationOutcome;
use Kongono\ModelInvocationRuntime\Exception\InvalidInvocationRuntimeException;
use Kongono\ModelInvocationRuntime\Execution\InvocationRuntimeResult;
use Kongono\ModelInvocationRuntime\Internal\ReceiptFactory;
use Kongono\ModelInvocationRuntime\Tests\Fixture\FakeInvocationAdapter;
use Kongono\ModelInvocationRuntime\Tests\Fixture\FakeInvocationAdapterSession;
use Kongono\ModelInvocationRuntime\Tests\Fixture\RuntimeFixture;
use PHPUnit\Framework\TestCase;

final class InvocationRuntimeResultTest extends TestCase
{
    public function testSucceededRelationsAndDetachedEventsAreEnforced(): void
    {
        $source = RuntimeFixture::source();
        $request = RuntimeFixture::request($source, new FakeInvocationAdapter(new AdapterIdentity('adapter.test'), new FakeInvocationAdapterSession([])));
        $result = RuntimeFixture::result($source);
        $receipt = ReceiptFactory::create($request, InvocationOutcome::Succeeded, 1, $result, true);
        $runtimeResult = new InvocationRuntimeResult($receipt, $result, [], InvocationState::Retired, 4, 2);
        $events = $runtimeResult->events();
        $events[] = RuntimeFixture::event(RuntimeFixture::source(streaming: true), 1, \Kongono\ModelInvocationContract\Stream\StreamEventKind::Started)->eventValue();
        self::assertSame([], $runtimeResult->events());
        self::assertSame($result, $runtimeResult->result());
        self::assertSame(4, $runtimeResult->elapsedMilliseconds());
    }

    public function testInvalidTerminalRelationRejects(): void
    {
        $source = RuntimeFixture::source();
        $request = RuntimeFixture::request($source, new FakeInvocationAdapter(new AdapterIdentity('adapter.test'), new FakeInvocationAdapterSession([])));
        $receipt = ReceiptFactory::create($request, InvocationOutcome::Failed, 1, null, true);
        $this->expectException(InvalidInvocationRuntimeException::class);
        new InvocationRuntimeResult($receipt, null, [], InvocationState::Failed, 0, 0);
    }
}
