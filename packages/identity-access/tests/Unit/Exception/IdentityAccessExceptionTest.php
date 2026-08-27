<?php

declare(strict_types=1);

namespace Kongono\IdentityAccess\Tests\Unit\Exception;

use Kongono\IdentityAccess\Diagnostic\IdentityAccessDiagnostic;
use Kongono\IdentityAccess\Exception\ExceptionInterface;
use Kongono\IdentityAccess\Exception\IdentityAccessException;
use Kongono\IdentityAccess\Exception\InvalidIdentityAccessValueException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

final class IdentityAccessExceptionTest extends TestCase
{
    public function testMarkerBaseAndConcreteMembershipAndStatusShapeAreExact(): void
    {
        $marker = new ReflectionClass(ExceptionInterface::class);
        $base = new ReflectionClass(IdentityAccessException::class);
        $concrete = new ReflectionClass(InvalidIdentityAccessValueException::class);
        self::assertContains(\Throwable::class, $marker->getInterfaceNames());
        $baseParent = $base->getParentClass();
        self::assertInstanceOf(ReflectionClass::class, $baseParent);
        self::assertSame(RuntimeException::class, $baseParent->getName());
        self::assertContains(ExceptionInterface::class, $base->getInterfaceNames());
        $concreteParent = $concrete->getParentClass();
        self::assertInstanceOf(ReflectionClass::class, $concreteParent);
        self::assertSame(IdentityAccessException::class, $concreteParent->getName());
        self::assertTrue($base->isAbstract());
        self::assertTrue($concrete->isFinal());
        self::assertTrue($concrete->getConstructor()?->isPrivate());
        self::assertSame(
            ['authentication', 'authorization', 'principal', 'resource', 'securityContext'],
            $this->declaredPublicMethods($concrete),
        );
    }

    public function testExceptionPresentationIsNonDisclosingAndPreviousCauseIsRetained(): void
    {
        $raw = 'raw-rejected-principal-value';
        $exception = InvalidIdentityAccessValueException::principal();
        self::assertSame('KGN-ACCESS-1001', $exception->diagnosticId());
        self::assertSame('Invalid principal value', $exception->getMessage());
        self::assertSame('KGN-ACCESS-1001: Invalid principal value', (string) $exception);
        self::assertStringNotContainsString($raw, $exception->getMessage());
        self::assertStringNotContainsString($raw, (string) $exception);
        self::assertSame($exception->diagnosticContext(), $exception->diagnosticContext());

        $previous = new RuntimeException('protected cause');
        $withPrevious = new class ($previous) extends IdentityAccessException {
            public function __construct(\Throwable $previous)
            {
                parent::__construct(IdentityAccessDiagnostic::Authentication, $previous);
            }
        };
        self::assertSame($previous, $withPrevious->getPrevious());
        self::assertSame('KGN-ACCESS-1002: Invalid authentication outcome', (string) $withPrevious);
        self::assertStringNotContainsString('protected cause', (string) $withPrevious);
    }

    public function testEveryFactoryUsesItsExactDiagnostic(): void
    {
        self::assertSame('KGN-ACCESS-1001', InvalidIdentityAccessValueException::principal()->diagnosticId());
        self::assertSame('KGN-ACCESS-1002', InvalidIdentityAccessValueException::authentication()->diagnosticId());
        self::assertSame('KGN-ACCESS-1003', InvalidIdentityAccessValueException::authorization()->diagnosticId());
        self::assertSame('KGN-ACCESS-1004', InvalidIdentityAccessValueException::resource()->diagnosticId());
        self::assertSame('KGN-ACCESS-1005', InvalidIdentityAccessValueException::securityContext()->diagnosticId());
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
            if ($method->getDeclaringClass()->getName() === $reflection->getName() && $method->isPublic()) {
                $methods[] = $method->getName();
            }
        }
        sort($methods);

        return $methods;
    }
}
