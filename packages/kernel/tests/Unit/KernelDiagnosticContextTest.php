<?php

declare(strict_types=1);

namespace Kongono\Kernel\Tests\Unit;

use Kongono\ApplicationGraph\Exception\ExceptionInterface as GraphExceptionInterface;
use Kongono\DependencyInjection\Exception\ExceptionInterface as DependencyInjectionExceptionInterface;
use Kongono\Diagnostics\Context\DiagnosticContext;
use Kongono\Diagnostics\Context\DiagnosticField;
use Kongono\Diagnostics\Exception\ExceptionInterface as DiagnosticsExceptionInterface;
use Kongono\Kernel\Diagnostic\LifecycleDiagnostic;
use Kongono\Kernel\Exception\ExceptionInterface as KernelExceptionInterface;
use Kongono\Kernel\Exception\KernelException;
use Kongono\Kernel\Exception\LifecycleException;
use Kongono\Kernel\Kernel;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use RuntimeException;
use Throwable;

final class KernelDiagnosticContextTest extends TestCase
{
    public function testCatalogMetadataAndContextSchemasAreExact(): void
    {
        $expected = [
            'BootWhenNotUnbooted' => [
                'KGN-KERNEL-1001',
                'Boot requested when the Kernel is not `Unbooted`',
                'Boot each Kernel instance exactly once from `Unbooted`',
                'Boot Kernel',
                'Boot requires Unbooted',
                [
                    ['actual_state', 'Actual state', 'text', 'safe', true],
                    ['expected_state', 'Expected state', 'text', 'safe', true],
                ],
            ],
            'ShutdownWhenNotBooted' => [
                'KGN-KERNEL-1002',
                'Shutdown requested when the Kernel is not `Booted`',
                'Shut down only a booted, non-shutdown Kernel',
                'Shut down Kernel',
                'Shutdown requires Booted',
                [
                    ['actual_state', 'Actual state', 'text', 'safe', true],
                    ['expected_state', 'Expected state', 'text', 'safe', true],
                ],
            ],
            'ShutdownWithActiveOperations' => [
                'KGN-KERNEL-1003',
                'Shutdown requested while operations remain active',
                'End every operation owned by that Kernel before shutdown',
                'Shut down Kernel',
                'Shutdown requires zero active operations',
                [
                    ['active_operation_count', 'Active operation count', 'integer', 'safe', true],
                    ['expected_active_operation_count', 'Expected active operation count', 'integer', 'safe', true],
                ],
            ],
            'BeginOperationWhenNotBooted' => [
                'KGN-KERNEL-1004',
                'Operation begin requested when the Kernel is not `Booted`',
                'Boot first and do not begin work after shutdown',
                'Begin operation',
                'Beginning work requires Booted',
                [
                    ['actual_state', 'Actual state', 'text', 'safe', true],
                    ['expected_state', 'Expected state', 'text', 'safe', true],
                ],
            ],
            'EndOperationWhenNotBooted' => [
                'KGN-KERNEL-1005',
                'Operation end requested when the Kernel is not `Booted`',
                'End active scopes while their owning Kernel is booted',
                'End operation',
                'Ending a scope requires Booted',
                [
                    ['actual_state', 'Actual state', 'text', 'safe', true],
                    ['expected_state', 'Expected state', 'text', 'safe', true],
                ],
            ],
            'InactiveOrForeignOperationScope' => [
                'KGN-KERNEL-1006',
                'Operation scope is inactive, already ended, or owned by another Kernel',
                'Pass the exact active scope to the Kernel instance that created it',
                'End operation',
                'Scope must be active and owned by this Kernel',
                [['scope_condition', 'Scope condition', 'text', 'safe', true]],
            ],
        ];

        self::assertSame(array_keys($expected), array_map(
            static fn(LifecycleDiagnostic $diagnostic): string => $diagnostic->name,
            LifecycleDiagnostic::cases(),
        ));

        foreach (LifecycleDiagnostic::cases() as $diagnostic) {
            [$id, $title, $remediation, $operation, $invariant, $fields] = $expected[$diagnostic->name];
            $schema = $diagnostic->contextSchema();

            self::assertSame($id, $diagnostic->value);
            self::assertSame($title, $diagnostic->title());
            self::assertSame($remediation, $diagnostic->remediation());
            self::assertSame($operation, $schema->operation());
            self::assertSame($invariant, $schema->violatedInvariant());
            self::assertSame($fields, array_map(
                static fn($field): array => [
                    $field->key(),
                    $field->label(),
                    $field->kind()->value,
                    $field->disclosure()->value,
                    $field->isRequired(),
                ],
                $schema->fields(),
            ));
            self::assertSame(array_map(
                static fn(array $field): array => array_slice($field, 0, 4),
                $fields,
            ), array_map(
                static fn(DiagnosticField $field): array => [
                    $field->key(),
                    $field->label(),
                    $field->kind()->value,
                    $field->disclosure()->value,
                ],
                $diagnostic->context($this->completeValues($diagnostic))->fields(),
            ));
        }
    }

    public function testCatalogDeclaresOnlyAuthorizedMethodsAndConstructsCompleteSamples(): void
    {
        $reflection = new ReflectionClass(LifecycleDiagnostic::class);
        $methods = array_values(array_filter(
            $reflection->getMethods(),
            static fn(ReflectionMethod $method): bool => $method->isUserDefined()
                && LifecycleDiagnostic::class === $method->getDeclaringClass()->getName(),
        ));
        $methodNames = array_map(static fn(ReflectionMethod $method): string => $method->getName(), $methods);
        sort($methodNames);

        self::assertSame(['context', 'contextSchema', 'remediation', 'title'], $methodNames);

        foreach (LifecycleDiagnostic::cases() as $diagnostic) {
            $values = array_reverse($this->completeValues($diagnostic), true);
            $context = $diagnostic->context($values);
            $schema = $diagnostic->contextSchema();

            self::assertSame($schema->operation(), $context->operation());
            self::assertSame($schema->violatedInvariant(), $context->violatedInvariant());
            self::assertSame(
                array_map(static fn($field): string => $field->key(), $schema->fields()),
                array_map(static fn(DiagnosticField $field): string => $field->key(), $context->fields()),
            );
        }
    }

    public function testClosedSchemasRejectInvalidValuesThroughDiagnosticsBoundary(): void
    {
        foreach ([
            static fn(): DiagnosticContext => LifecycleDiagnostic::BootWhenNotUnbooted->context([
                'actual_state' => 'Unbooted',
            ]),
            static fn(): DiagnosticContext => LifecycleDiagnostic::BootWhenNotUnbooted->context([
                'actual_state' => 'Unbooted',
                'expected_state' => 'Unbooted',
                'unknown' => true,
            ]),
            static fn(): DiagnosticContext => LifecycleDiagnostic::BootWhenNotUnbooted->context([
                'actual_state' => 0,
                'expected_state' => 'Unbooted',
            ]),
        ] as $operation) {
            try {
                $operation();
                self::fail('Invalid Kernel context values must fail.');
            } catch (Throwable $exception) {
                self::assertInstanceOf(DiagnosticsExceptionInterface::class, $exception);
                self::assertNotInstanceOf(KernelExceptionInterface::class, $exception);
            }
        }

        $structurallyValidArbitraryToken = LifecycleDiagnostic::BootWhenNotUnbooted->context([
            'actual_state' => 'arbitrary',
            'expected_state' => 'token',
        ]);
        self::assertSame(['arbitrary', 'token'], array_map(
            static fn(DiagnosticField $field): string|int|bool => $field->value(),
            $structurallyValidArbitraryToken->fields(),
        ));
    }

    public function testManualExceptionPreservesNativeAndDiagnosticBehaviorWithSameContextIdentity(): void
    {
        $cause = new RuntimeException('cause');
        $exception = new LifecycleException(
            LifecycleDiagnostic::BootWhenNotUnbooted,
            'message',
            ['actual_state' => 'Booted', 'expected_state' => 'Unbooted'],
            $cause,
        );
        $context = $exception->diagnosticContext();

        self::assertSame('KGN-KERNEL-1001', $exception->diagnosticId());
        self::assertSame(LifecycleDiagnostic::BootWhenNotUnbooted->title(), $exception->diagnosticTitle());
        self::assertSame(LifecycleDiagnostic::BootWhenNotUnbooted->remediation(), $exception->diagnosticRemediation());
        self::assertSame('message', $exception->getMessage());
        self::assertSame(0, $exception->getCode());
        self::assertSame($cause, $exception->getPrevious());
        self::assertSame($context, $exception->diagnosticContext());

        try {
            new LifecycleException(
                LifecycleDiagnostic::BootWhenNotUnbooted,
                'invalid',
                ['actual_state' => 'Booted'],
            );
            self::fail('An invalid manual Kernel context must propagate.');
        } catch (Throwable $invalidContext) {
            self::assertInstanceOf(DiagnosticsExceptionInterface::class, $invalidContext);
            self::assertNotInstanceOf(KernelExceptionInterface::class, $invalidContext);
        }
    }

    public function testKernelMarkerAndBaseReflectionShapesAreExactAndPackageLocal(): void
    {
        $marker = new ReflectionClass(KernelExceptionInterface::class);
        $markerMethods = array_values(array_filter(
            $marker->getMethods(),
            static fn(ReflectionMethod $method): bool => KernelExceptionInterface::class === $method->getDeclaringClass()->getName(),
        ));
        $markerMethodNames = array_map(static fn(ReflectionMethod $method): string => $method->getName(), $markerMethods);
        sort($markerMethodNames);

        self::assertSame([Throwable::class, \Stringable::class], $marker->getInterfaceNames());
        self::assertSame(['diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'], $markerMethodNames);
        self::assertSame(DiagnosticContext::class, (string) $marker->getMethod('diagnosticContext')->getReturnType());
        self::assertSame([], $marker->getMethod('diagnosticContext')->getParameters());

        foreach ([GraphExceptionInterface::class, DependencyInjectionExceptionInterface::class, DiagnosticsExceptionInterface::class] as $other) {
            self::assertFalse(is_a(KernelExceptionInterface::class, $other, true));
            self::assertFalse(is_a($other, KernelExceptionInterface::class, true));
        }

        $base = new ReflectionClass(KernelException::class);
        $properties = array_values(array_filter(
            $base->getProperties(),
            static fn(ReflectionProperty $property): bool => KernelException::class === $property->getDeclaringClass()->getName(),
        ));
        self::assertSame(['diagnostic', 'diagnosticContext'], array_map(
            static fn(ReflectionProperty $property): string => $property->getName(),
            $properties,
        ));
        self::assertSame([LifecycleDiagnostic::class, DiagnosticContext::class], array_map(
            static fn(ReflectionProperty $property): string => (string) $property->getType(),
            $properties,
        ));

        foreach ($properties as $property) {
            self::assertTrue($property->isPrivate());
            self::assertTrue($property->isReadOnly());
            self::assertFalse($property->isStatic());
        }

        $methods = array_values(array_filter(
            $base->getMethods(),
            static fn(ReflectionMethod $method): bool => KernelException::class === $method->getDeclaringClass()->getName(),
        ));
        $methodNames = array_map(static fn(ReflectionMethod $method): string => $method->getName(), $methods);
        sort($methodNames);
        self::assertSame(['__construct', 'diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'], $methodNames);

        foreach ($methods as $method) {
            self::assertTrue($method->isFinal());
            self::assertTrue($method->isPublic());
        }

        $parameters = $base->getConstructor()?->getParameters();
        self::assertNotNull($parameters);
        self::assertCount(4, $parameters);
        self::assertSame([LifecycleDiagnostic::class, 'string', 'array', '?Throwable'], array_map(
            static fn($parameter): string => (string) $parameter->getType(),
            $parameters,
        ));
        self::assertTrue($parameters[3]->isDefaultValueAvailable());
        self::assertNull($parameters[3]->getDefaultValue());

        $markerSource = file_get_contents(dirname(__DIR__, 2) . '/src/Exception/ExceptionInterface.php');
        $baseSource = file_get_contents(dirname(__DIR__, 2) . '/src/Exception/KernelException.php');
        self::assertIsString($markerSource);
        self::assertIsString($baseSource);
        self::assertStringContainsString('Kongono\\Diagnostics\\Context\\DiagnosticContext', $markerSource);
        self::assertStringNotContainsString('LifecycleDiagnostic', $markerSource);
        self::assertStringNotContainsString('Kongono\\Diagnostics\\Diagnostic', $markerSource);
        self::assertStringNotContainsString('Kongono\\Diagnostics\\Exception', $baseSource);

        $catalogSource = file_get_contents(dirname(__DIR__, 2) . '/src/Diagnostic/LifecycleDiagnostic.php');
        $kernelSource = file_get_contents(dirname(__DIR__, 2) . '/src/Kernel.php');
        self::assertIsString($catalogSource);
        self::assertIsString($kernelSource);

        foreach ([$catalogSource, $baseSource, $kernelSource] as $source) {
            foreach ([
                'file_get_contents(',
                'file_put_contents(',
                'fopen(',
                'getenv(',
                '$_ENV',
                '$_SERVER',
                'error_log(',
                '->render(',
                '->log(',
                '->record(',
                '->cache(',
            ] as $forbiddenCall) {
                self::assertStringNotContainsString($forbiddenCall, $source);
            }
        }

        foreach ([LifecycleDiagnostic::class, KernelException::class, Kernel::class] as $type) {
            foreach ((new ReflectionClass($type))->getProperties() as $property) {
                self::assertFalse($property->isStatic());
            }
        }
    }

    public function testContextValuesAndReturnedArraysAreDetachedAndExceptionsDoNotShareContext(): void
    {
        $state = 'Booted';
        $values = ['actual_state' => &$state, 'expected_state' => 'Unbooted'];
        $first = new LifecycleException(LifecycleDiagnostic::BootWhenNotUnbooted, 'first', $values);
        $second = new LifecycleException(LifecycleDiagnostic::BootWhenNotUnbooted, 'second', $values);
        $fields = $first->diagnosticContext()->fields();
        $firstSchema = LifecycleDiagnostic::BootWhenNotUnbooted->contextSchema();
        $secondSchema = LifecycleDiagnostic::BootWhenNotUnbooted->contextSchema();

        $state = 'changed';
        array_pop($fields);

        self::assertNotSame($first->diagnosticContext(), $second->diagnosticContext());
        self::assertNotSame($firstSchema, $secondSchema);
        self::assertSame('Booted', $first->diagnosticContext()->fields()[0]->value());
        self::assertSame('Unbooted', $first->diagnosticContext()->fields()[1]->value());
        self::assertCount(2, $first->diagnosticContext()->fields());
        self::assertCount(2, $second->diagnosticContext()->fields());
    }

    public function testPublicMarkerDoesNotExposeInternalCatalogType(): void
    {
        $marker = new ReflectionClass(KernelExceptionInterface::class);

        foreach ($marker->getMethods() as $method) {
            $returnType = $method->getReturnType();

            if ($returnType instanceof ReflectionNamedType) {
                self::assertNotSame(LifecycleDiagnostic::class, $returnType->getName());
            }

            foreach ($method->getParameters() as $parameter) {
                self::assertNotSame(LifecycleDiagnostic::class, (string) $parameter->getType());
            }
        }
    }

    /** @return array<string, string|int|bool> */
    private function completeValues(LifecycleDiagnostic $diagnostic): array
    {
        return match ($diagnostic) {
            LifecycleDiagnostic::BootWhenNotUnbooted => [
                'actual_state' => 'Booted',
                'expected_state' => 'Unbooted',
            ],
            LifecycleDiagnostic::ShutdownWhenNotBooted,
            LifecycleDiagnostic::BeginOperationWhenNotBooted,
            LifecycleDiagnostic::EndOperationWhenNotBooted => [
                'actual_state' => 'Unbooted',
                'expected_state' => 'Booted',
            ],
            LifecycleDiagnostic::ShutdownWithActiveOperations => [
                'active_operation_count' => 2,
                'expected_active_operation_count' => 0,
            ],
            LifecycleDiagnostic::InactiveOrForeignOperationScope => [
                'scope_condition' => 'inactive_or_foreign',
            ],
        };
    }
}
