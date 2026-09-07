<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationRuntime\Tests\Unit\Exception;

use Kongono\ModelInvocationBuild\Plan\AdapterIdentity;
use Kongono\ModelInvocationContract\Receipt\InvocationOutcome;
use Kongono\ModelInvocationRuntime\Exception\InvocationCleanupException;
use Kongono\ModelInvocationRuntime\Exception\InvocationOperationAndCleanupException;
use Kongono\ModelInvocationRuntime\Internal\ReceiptFactory;
use Kongono\ModelInvocationRuntime\Tests\Fixture\FakeInvocationAdapter;
use Kongono\ModelInvocationRuntime\Tests\Fixture\FakeInvocationAdapterSession;
use Kongono\ModelInvocationRuntime\Tests\Fixture\RuntimeFixture;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ModelInvocationRuntimeExceptionTest extends TestCase
{
    public function testCleanupExceptionsDetachAndPreserveExactThrowableObjects(): void
    {
        $request = RuntimeFixture::request(RuntimeFixture::source(), new FakeInvocationAdapter(new AdapterIdentity('adapter.test'), new FakeInvocationAdapterSession([])));
        $receipt = ReceiptFactory::create($request, InvocationOutcome::Uncertain, 1, null, false);
        $primary = new RuntimeException('secret primary');
        $cleanup = new RuntimeException('secret cleanup');
        $only = new InvocationCleanupException($receipt, [$cleanup]);
        self::assertSame([$cleanup], $only->cleanupFailures());
        self::assertStringNotContainsString('secret cleanup', (string) $only);

        $combined = new InvocationOperationAndCleanupException($receipt, $primary, [$cleanup]);
        self::assertSame($primary, $combined->primaryFailure());
        self::assertSame([$cleanup], $combined->cleanupFailures());
        self::assertStringNotContainsString('secret primary', (string) $combined);
    }
}
