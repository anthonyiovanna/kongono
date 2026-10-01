<?php

declare(strict_types=1);

namespace Kongono\EventRuntime\Tests\Unit\Exception;

use Kongono\EventRuntime\Exception\ExceptionInterface;
use Kongono\EventRuntime\Exception\ReentrantDispatchException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ReentrantDispatchExceptionTest extends TestCase
{
    public function testFactoryBuildsFreshFixedPresentation(): void
    {
        $first = ReentrantDispatchException::active();
        $second = ReentrantDispatchException::active();
        self::assertNotSame($first, $second);
        self::assertInstanceOf(ExceptionInterface::class, $first);
        self::assertSame('Event dispatch is already active.', $first->getMessage());
        self::assertSame(null, $first->getPrevious());
        self::assertSame('KGN-EVENT-RUNTIME-2221', $first->diagnosticId());
        self::assertSame('Event dispatch is already active', $first->diagnosticTitle());
        self::assertSame('Wait for the active dispatch to finish or use a separate dispatcher instance', $first->diagnosticRemediation());
        self::assertSame('reentrant_dispatch', $first->diagnosticContext()->fields()[0]->value());
        self::assertSame('Kongono\EventRuntime\Exception\ReentrantDispatchException: [KGN-EVENT-RUNTIME-2221] Event dispatch is already active. Remediation: Wait for the active dispatch to finish or use a separate dispatcher instance. Context: violation=reentrant_dispatch', (string) $first);
    }

    public function testConstructorIsPrivateAndParameterless(): void
    {
        $constructor = (new ReflectionClass(ReentrantDispatchException::class))->getConstructor();
        self::assertNotNull($constructor);
        self::assertTrue($constructor->isPrivate());
        self::assertSame(0, $constructor->getNumberOfParameters());
    }
}
