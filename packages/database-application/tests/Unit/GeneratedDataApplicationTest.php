<?php

declare(strict_types=1);

namespace Kongono\DatabaseApplication\Tests\Unit;

use Kongono\Composition\Runtime\LoadedGeneratedWiring;
use Kongono\DatabaseApplication\Exception\DataApplicationLifecycleException;
use Kongono\DatabaseApplication\Exception\InvalidGeneratedDataProfileException;
use Kongono\DatabaseApplication\GeneratedDataApplication;
use Kongono\DatabaseApplication\Tests\Fixture\InvalidApplicationRootAssembly;
use Kongono\DatabaseApplication\Tests\Fixture\TestApplicationAssembly;
use Kongono\DatabaseApplication\Tests\Fixture\TestInput;
use Kongono\DatabaseApplication\Tests\Fixture\TestOperationAssembly;
use Kongono\DatabaseApplication\Tests\Fixture\TestRoot;
use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class GeneratedDataApplicationTest extends TestCase
{
    protected function setUp(): void
    {
        TestApplicationAssembly::reset();
        InvalidApplicationRootAssembly::$startCalls = 0;
    }

    public function testSequentialAndOverlappingOperationsHaveDistinctRootsAndIndependentClose(): void
    {
        $application = GeneratedDataApplication::start($this->loaded(), new TestInput());
        $first = $application->beginOperation();
        $second = $application->beginOperation();
        self::assertInstanceOf(TestRoot::class, $first->root());
        self::assertInstanceOf(TestRoot::class, $second->root());
        self::assertNotSame($first->root(), $second->root());
        self::assertNotSame($first->root()->ordinal, $second->root()->ordinal);

        $first->close();
        self::assertSame(2, $second->root()->ordinal);
        $this->expectException(LogicException::class);
        try {
            $application->shutdown();
        } finally {
            $second->close();
            $application->shutdown();
            self::assertTrue($application->isRetired());
            self::assertSame(2, TestOperationAssembly::$closeCalls);
        }
    }

    public function testProfileValidationOccursBeforeStartup(): void
    {
        $loaded = new LoadedGeneratedWiring(
            str_repeat('b', 64),
            InvalidApplicationRootAssembly::class,
            TestOperationAssembly::class,
            new ReflectionMethod(InvalidApplicationRootAssembly::class, 'start'),
        );

        $this->expectException(InvalidGeneratedDataProfileException::class);
        try {
            GeneratedDataApplication::start($loaded, new TestInput());
        } finally {
            self::assertSame(0, InvalidApplicationRootAssembly::$startCalls);
        }
    }

    public function testClosedOperationAndRetiredApplicationRejectFurtherUse(): void
    {
        $application = GeneratedDataApplication::start($this->loaded(), new TestInput());
        $operation = $application->beginOperation();
        $operation->close();
        $operation->close();
        try {
            $operation->root();
            self::fail('A closed operation retained its root.');
        } catch (DataApplicationLifecycleException) {
        }
        $application->shutdown();

        $this->expectException(DataApplicationLifecycleException::class);
        $application->beginOperation();
    }

    private function loaded(): LoadedGeneratedWiring
    {
        return new LoadedGeneratedWiring(
            str_repeat('a', 64),
            TestApplicationAssembly::class,
            TestOperationAssembly::class,
            new ReflectionMethod(TestApplicationAssembly::class, 'start'),
        );
    }
}
