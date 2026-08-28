<?php

declare(strict_types=1);

namespace Kongono\Observability\Tests\Unit\Exception;

use Kongono\Observability\Diagnostic\ObservabilityDiagnostic;
use Kongono\Observability\Exception\ExceptionInterface;
use Kongono\Observability\Exception\InvalidObservabilityValueException;
use Kongono\Observability\Exception\ObservabilityException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

final class ObservabilityExceptionTest extends TestCase
{
    public function testCatchBoundaryBaseAndConcreteSurfaceAreExact(): void
    {
        $marker = new ReflectionClass(ExceptionInterface::class);
        $base = new ReflectionClass(ObservabilityException::class);
        $concrete = new ReflectionClass(InvalidObservabilityValueException::class);
        self::assertContains(\Throwable::class, $marker->getInterfaceNames());
        self::assertSame(['diagnosticContext', 'diagnosticId', 'diagnosticRemediation', 'diagnosticTitle'], $this->declaredPublicMethods($marker));
        $baseParent = $base->getParentClass();
        self::assertNotFalse($baseParent);
        self::assertSame(RuntimeException::class, $baseParent->getName());
        self::assertContains(ExceptionInterface::class, $base->getInterfaceNames());
        self::assertTrue($base->isAbstract());
        self::assertTrue($concrete->isFinal());
        self::assertTrue($concrete->getConstructor()?->isPrivate());
        self::assertSame(['attribute', 'context', 'signalIdentity'], $this->declaredPublicMethods($concrete));
    }

    public function testFactoriesAreEagerDeterministicAndNondisclosing(): void
    {
        $rawValues = ['rejected-key', "\xC3\x28", 'secret-correlation', 'signal-name', '65536', 'INF'];
        $exceptions = [
            InvalidObservabilityValueException::context(),
            InvalidObservabilityValueException::attribute(),
            InvalidObservabilityValueException::signalIdentity(),
        ];
        self::assertSame(['KGN-OBSERVABILITY-1001', 'KGN-OBSERVABILITY-1002', 'KGN-OBSERVABILITY-1003'], array_map(static fn($exception): string => $exception->diagnosticId(), $exceptions));
        foreach ($exceptions as $exception) {
            self::assertSame($exception->diagnosticContext(), $exception->diagnosticContext());
            self::assertSame($exception->diagnosticId() . ': ' . $exception->diagnosticTitle(), (string) $exception);
            foreach ($rawValues as $raw) {
                self::assertStringNotContainsString($raw, $exception->getMessage());
                self::assertStringNotContainsString($raw, (string) $exception);
                foreach ($exception->diagnosticContext()->fields() as $field) {
                    self::assertNotSame($raw, $field->value());
                }
            }
        }

        $previous = new RuntimeException('protected cause');
        $withPrevious = new class ($previous) extends ObservabilityException {
            public function __construct(\Throwable $previous)
            {
                parent::__construct(ObservabilityDiagnostic::Context, $previous);
            }
        };
        self::assertSame($previous, $withPrevious->getPrevious());
        self::assertStringNotContainsString('protected cause', (string) $withPrevious);
    }

    /**
     * @template T of object
     * @param ReflectionClass<T> $reflection
     * @return list<string>
     */
    private function declaredPublicMethods(ReflectionClass $reflection): array
    {
        $methods = [];
        foreach ($reflection->getMethods() as $method) {
            if ($method->isPublic() && $method->getDeclaringClass()->getName() === $reflection->getName()) {
                $methods[] = $method->getName();
            }
        }
        sort($methods);

        return $methods;
    }
}
