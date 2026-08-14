<?php

declare(strict_types=1);

namespace Kongono\Kernel\Tests\Unit;

use Kongono\Kernel\Diagnostic\LifecycleDiagnostic;
use Kongono\Kernel\Exception\ExceptionInterface;
use Kongono\Kernel\Exception\LifecycleException;
use Kongono\Kernel\Kernel;
use Kongono\Kernel\Lifecycle\KernelState;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use Throwable;

final class KernelTest extends TestCase
{
    public function testLifecycleDiagnosticCatalogAndMetadataContract(): void
    {
        $expected = [
            ['BootWhenNotUnbooted', 'KGN-KERNEL-1001', 'Boot requested when the Kernel is not `Unbooted`', 'Boot each Kernel instance exactly once from `Unbooted`'],
            ['ShutdownWhenNotBooted', 'KGN-KERNEL-1002', 'Shutdown requested when the Kernel is not `Booted`', 'Shut down only a booted, non-shutdown Kernel'],
            ['ShutdownWithActiveOperations', 'KGN-KERNEL-1003', 'Shutdown requested while operations remain active', 'End every operation owned by that Kernel before shutdown'],
            ['BeginOperationWhenNotBooted', 'KGN-KERNEL-1004', 'Operation begin requested when the Kernel is not `Booted`', 'Boot first and do not begin work after shutdown'],
            ['EndOperationWhenNotBooted', 'KGN-KERNEL-1005', 'Operation end requested when the Kernel is not `Booted`', 'End active scopes while their owning Kernel is booted'],
            ['InactiveOrForeignOperationScope', 'KGN-KERNEL-1006', 'Operation scope is inactive, already ended, or owned by another Kernel', 'Pass the exact active scope to the Kernel instance that created it'],
        ];
        $actual = array_map(
            static fn(LifecycleDiagnostic $diagnostic): array => [
                $diagnostic->name,
                $diagnostic->value,
                $diagnostic->title(),
                $diagnostic->remediation(),
            ],
            LifecycleDiagnostic::cases(),
        );
        $reflection = new ReflectionClass(LifecycleDiagnostic::class);
        $declaredMethods = array_values(array_filter(
            $reflection->getMethods(),
            static fn(\ReflectionMethod $method): bool => $method->isUserDefined()
                && LifecycleDiagnostic::class === $method->getDeclaringClass()->getName(),
        ));
        $declaredProperties = array_values(array_filter(
            $reflection->getProperties(),
            static fn(\ReflectionProperty $property): bool => LifecycleDiagnostic::class === $property->getDeclaringClass()->getName(),
        ));

        self::assertSame($expected, $actual);
        self::assertSame(['context', 'contextSchema', 'remediation', 'title'], $this->sortedMethodNames($declaredMethods));
        self::assertSame(
            ['name', 'value'],
            array_map(static fn(\ReflectionProperty $property): string => $property->getName(), $declaredProperties),
        );
        self::assertFalse($reflection->hasMethod('__toString'));

        $cause = new RuntimeException('cause');
        $exception = new LifecycleException(
            LifecycleDiagnostic::BootWhenNotUnbooted,
            'message',
            ['actual_state' => 'Booted', 'expected_state' => 'Unbooted'],
            $cause,
        );

        self::assertSame('KGN-KERNEL-1001', $exception->diagnosticId());
        self::assertSame(LifecycleDiagnostic::BootWhenNotUnbooted->title(), $exception->diagnosticTitle());
        self::assertSame(LifecycleDiagnostic::BootWhenNotUnbooted->remediation(), $exception->diagnosticRemediation());
        self::assertSame('message', $exception->getMessage());
        self::assertSame(0, $exception->getCode());
        self::assertSame($cause, $exception->getPrevious());
    }

    public function testEveryLifecycleFailureUsesItsExactDiagnostic(): void
    {
        $booted = new Kernel();
        $booted->boot();
        $active = new Kernel();
        $active->boot();
        $active->beginOperation();
        $shutdown = new Kernel();
        $shutdown->boot();
        $ended = $shutdown->beginOperation();
        $shutdown->endOperation($ended);
        $shutdown->shutdown();
        $owner = new Kernel();
        $foreign = new Kernel();
        $owner->boot();
        $foreign->boot();
        $foreignScope = $owner->beginOperation();

        $this->assertLifecycleDiagnostic(
            static function () use ($booted): void {
                $booted->boot();
            },
            LifecycleDiagnostic::BootWhenNotUnbooted,
            'Cannot boot Kernel while in Booted state.',
            ['actual_state' => 'Booted', 'expected_state' => 'Unbooted'],
        );
        $this->assertLifecycleDiagnostic(
            static function (): void {
                (new Kernel())->shutdown();
            },
            LifecycleDiagnostic::ShutdownWhenNotBooted,
            'Cannot shut down Kernel while in Unbooted state.',
            ['actual_state' => 'Unbooted', 'expected_state' => 'Booted'],
        );
        $this->assertLifecycleDiagnostic(
            static function () use ($active): void {
                $active->shutdown();
            },
            LifecycleDiagnostic::ShutdownWithActiveOperations,
            'Cannot shut down Kernel while 1 operation(s) remain active.',
            ['active_operation_count' => 1, 'expected_active_operation_count' => 0],
        );
        $this->assertLifecycleDiagnostic(
            static function (): void {
                (new Kernel())->beginOperation();
            },
            LifecycleDiagnostic::BeginOperationWhenNotBooted,
            'Cannot begin an operation while Kernel is in Unbooted state.',
            ['actual_state' => 'Unbooted', 'expected_state' => 'Booted'],
        );
        $this->assertLifecycleDiagnostic(
            static function () use ($shutdown, $ended): void {
                $shutdown->endOperation($ended);
            },
            LifecycleDiagnostic::EndOperationWhenNotBooted,
            'Cannot end an operation while Kernel is in Shutdown state.',
            ['actual_state' => 'Shutdown', 'expected_state' => 'Booted'],
        );
        $this->assertLifecycleDiagnostic(
            static function () use ($foreign, $foreignScope): void {
                $foreign->endOperation($foreignScope);
            },
            LifecycleDiagnostic::InactiveOrForeignOperationScope,
            'Cannot end an operation that is not active and owned by this Kernel.',
            ['scope_condition' => 'inactive_or_foreign'],
        );
    }

    public function testNewKernelIsUnbootedWithNoActiveOperations(): void
    {
        $kernel = new Kernel();

        self::assertSame(KernelState::Unbooted, $kernel->state());
        self::assertSame(0, $kernel->activeOperationCount());
    }

    public function testKernelBootsOnce(): void
    {
        $kernel = new Kernel();

        $kernel->boot();

        self::assertSame(KernelState::Booted, $kernel->state());
        self::assertSame(0, $kernel->activeOperationCount());
    }

    public function testKernelCannotBootTwiceWithoutStateMutation(): void
    {
        $kernel = new Kernel();
        $kernel->boot();

        try {
            $kernel->boot();
            self::fail('A repeated boot must fail.');
        } catch (LifecycleException $exception) {
            self::assertStringContainsString('boot', strtolower($exception->getMessage()));
        }

        self::assertSame(KernelState::Booted, $kernel->state());
        self::assertSame(0, $kernel->activeOperationCount());
    }

    public function testOperationCannotBeginBeforeBoot(): void
    {
        $kernel = new Kernel();

        $this->expectException(LifecycleException::class);
        $this->expectExceptionMessageMatches('/begin an operation/i');

        $kernel->beginOperation();
    }

    public function testOperationCannotBeginAfterShutdown(): void
    {
        $kernel = new Kernel();
        $kernel->boot();
        $kernel->shutdown();

        $this->expectException(LifecycleException::class);
        $this->expectExceptionMessageMatches('/begin an operation/i');

        $kernel->beginOperation();
    }

    public function testOperationsAreDistinctAndMayOverlap(): void
    {
        $kernel = new Kernel();
        $kernel->boot();

        $first = $kernel->beginOperation();
        $second = $kernel->beginOperation();

        self::assertNotSame($first, $second);
        self::assertSame(2, $kernel->activeOperationCount());
    }

    public function testEndingOneOperationLeavesOtherOperationsActive(): void
    {
        $kernel = new Kernel();
        $kernel->boot();
        $first = $kernel->beginOperation();
        $second = $kernel->beginOperation();

        $kernel->endOperation($first);

        self::assertSame(1, $kernel->activeOperationCount());
        $kernel->endOperation($second);
        self::assertSame(0, $kernel->activeOperationCount());
    }

    public function testForeignOperationIsRejectedWithoutMutatingEitherKernel(): void
    {
        $owner = new Kernel();
        $foreign = new Kernel();
        $owner->boot();
        $foreign->boot();
        $operation = $owner->beginOperation();

        try {
            $foreign->endOperation($operation);
            self::fail('A foreign operation must fail.');
        } catch (LifecycleException $exception) {
            self::assertStringContainsString('owned', strtolower($exception->getMessage()));
        }

        self::assertSame(1, $owner->activeOperationCount());
        self::assertSame(0, $foreign->activeOperationCount());
        self::assertSame(KernelState::Booted, $owner->state());
        self::assertSame(KernelState::Booted, $foreign->state());
    }

    public function testOperationCannotEndTwiceWithoutCountCorruption(): void
    {
        $kernel = new Kernel();
        $kernel->boot();
        $operation = $kernel->beginOperation();
        $kernel->endOperation($operation);

        try {
            $kernel->endOperation($operation);
            self::fail('An already-ended operation must fail.');
        } catch (LifecycleException $exception) {
            self::assertStringContainsString('not active', strtolower($exception->getMessage()));
        }

        self::assertSame(0, $kernel->activeOperationCount());
        self::assertSame(KernelState::Booted, $kernel->state());
    }

    public function testShutdownWithActiveOperationsFailsWithoutMutation(): void
    {
        $kernel = new Kernel();
        $kernel->boot();
        $kernel->beginOperation();

        try {
            $kernel->shutdown();
            self::fail('Shutdown with active operations must fail.');
        } catch (LifecycleException $exception) {
            self::assertStringContainsString('remain active', strtolower($exception->getMessage()));
        }

        self::assertSame(KernelState::Booted, $kernel->state());
        self::assertSame(1, $kernel->activeOperationCount());
    }

    public function testKernelShutsDownAfterAllOperationsEnd(): void
    {
        $kernel = new Kernel();
        $kernel->boot();
        $operation = $kernel->beginOperation();
        $kernel->endOperation($operation);

        $kernel->shutdown();

        self::assertSame(KernelState::Shutdown, $kernel->state());
        self::assertSame(0, $kernel->activeOperationCount());
    }

    public function testRepeatedShutdownFailsWithoutMutation(): void
    {
        $kernel = new Kernel();
        $kernel->boot();
        $kernel->shutdown();

        try {
            $kernel->shutdown();
            self::fail('A repeated shutdown must fail.');
        } catch (LifecycleException $exception) {
            self::assertStringContainsString('shut down', strtolower($exception->getMessage()));
        }

        self::assertSame(KernelState::Shutdown, $kernel->state());
        self::assertSame(0, $kernel->activeOperationCount());
    }

    public function testEndedOperationCannotBeEndedAfterShutdown(): void
    {
        $kernel = new Kernel();
        $kernel->boot();
        $operation = $kernel->beginOperation();
        $kernel->endOperation($operation);
        $kernel->shutdown();

        try {
            $kernel->endOperation($operation);
            self::fail('An operation cannot end after Kernel shutdown.');
        } catch (LifecycleException $exception) {
            self::assertStringContainsString('shutdown', strtolower($exception->getMessage()));
        }

        self::assertSame(KernelState::Shutdown, $kernel->state());
        self::assertSame(0, $kernel->activeOperationCount());
    }

    public function testLifecycleFailuresImplementPublicExceptionBoundary(): void
    {
        $kernel = new Kernel();

        try {
            $kernel->shutdown();
            self::fail('Shutdown before boot must fail.');
        } catch (Throwable $exception) {
            self::assertInstanceOf(LifecycleException::class, $exception);
            self::assertInstanceOf(ExceptionInterface::class, $exception);
        }
    }

    public function testShutdownKernelCannotBeBootedAgain(): void
    {
        $kernel = new Kernel();
        $kernel->boot();
        $kernel->shutdown();

        try {
            $kernel->boot();
            self::fail('A shutdown Kernel must not boot again.');
        } catch (LifecycleException $exception) {
            self::assertStringContainsString('boot', strtolower($exception->getMessage()));
        }

        self::assertSame(KernelState::Shutdown, $kernel->state());
    }

    /**
     * @param callable(): void                    $operation
     * @param array<string, string|int|bool>      $contextValues
     */
    private function assertLifecycleDiagnostic(
        callable $operation,
        LifecycleDiagnostic $diagnostic,
        string $message,
        array $contextValues,
    ): void {
        try {
            $operation();
            self::fail('Lifecycle misuse must fail.');
        } catch (LifecycleException $exception) {
            self::assertSame($diagnostic->value, $exception->diagnosticId());
            self::assertSame($diagnostic->title(), $exception->diagnosticTitle());
            self::assertSame($diagnostic->remediation(), $exception->diagnosticRemediation());
            self::assertSame($message, $exception->getMessage());
            self::assertSame(0, $exception->getCode());
            self::assertNull($exception->getPrevious());
            self::assertSame($contextValues, array_reduce(
                $exception->diagnosticContext()->fields(),
                static function (array $values, \Kongono\Diagnostics\Context\DiagnosticField $field): array {
                    $values[$field->key()] = $field->value();

                    return $values;
                },
                [],
            ));
        }
    }

    /**
     * @param list<\ReflectionMethod> $methods
     *
     * @return list<string>
     */
    private function sortedMethodNames(array $methods): array
    {
        $names = array_map(static fn(\ReflectionMethod $method): string => $method->getName(), $methods);
        sort($names);

        return $names;
    }
}
