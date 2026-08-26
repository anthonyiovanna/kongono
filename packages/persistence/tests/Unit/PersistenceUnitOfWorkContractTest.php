<?php

declare(strict_types=1);

namespace Kongono\Persistence\Tests\Unit;

use Kongono\ApiMetadata\Api;
use Kongono\ApiMetadata\ApiStatus;
use Kongono\Persistence\ChangeExecutionOutcome;
use Kongono\Persistence\OperationPersistenceContext;
use Kongono\Persistence\PersistenceChangeExecutor;
use Kongono\Persistence\PersistenceStateAdapter;
use Kongono\Persistence\PersistenceUnitOfWork;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class PersistenceUnitOfWorkContractTest extends TestCase
{
    public function testExactInterfacesEnumAndContextSurface(): void
    {
        self::assertSame(['Applied', 'EntityAbsent', 'OptimisticConflict'], array_map(static fn(ChangeExecutionOutcome $case): string => $case->name, ChangeExecutionOutcome::cases()));

        foreach ([PersistenceStateAdapter::class, PersistenceChangeExecutor::class, PersistenceUnitOfWork::class] as $interface) {
            $reflection = new ReflectionClass($interface);
            self::assertTrue($reflection->isInterface());
            self::assertSame(ApiStatus::Experimental, $reflection->getAttributes(Api::class)[0]->newInstance()->status);
        }

        $context = new ReflectionClass(OperationPersistenceContext::class);
        self::assertTrue($context->isFinal());
        self::assertTrue($context->implementsInterface(PersistenceUnitOfWork::class));
        self::assertFalse($context->isCloneable());
        $constructor = $context->getConstructor();
        self::assertNotNull($constructor);
        self::assertFalse($constructor->isPublic());
        self::assertSame([
            'changes',
            'entityForIdentity',
            'entityState',
            'flush',
            'forManaged',
            'forNew',
            'identityOf',
            'markRemoved',
            'retire',
            'state',
        ], $this->publicMethodNames($context));
    }

    public function testExactCapabilityMethodSignatures(): void
    {
        $adapter = new ReflectionClass(PersistenceStateAdapter::class);
        self::assertSame(['changes', 'deletion', 'entityType', 'identity', 'insertion', 'snapshot'], $this->publicMethodNames($adapter));
        $executor = new ReflectionMethod(PersistenceChangeExecutor::class, 'execute');
        self::assertSame(['Kongono\\Database\\DatabaseExecutor', 'Kongono\\Persistence\\PersistenceChange'], array_map(static fn($parameter): string => (string) $parameter->getType(), $executor->getParameters()));
        self::assertSame(ChangeExecutionOutcome::class, (string) $executor->getReturnType());
        $unit = new ReflectionClass(PersistenceUnitOfWork::class);
        self::assertSame(['changes', 'entityForIdentity', 'entityState', 'flush', 'identityOf', 'markRemoved', 'retire', 'state'], $this->publicMethodNames($unit));
    }

    /**
     * @template T of object
     * @param ReflectionClass<T> $reflection
     * @return list<string>
     */
    private function publicMethodNames(ReflectionClass $reflection): array
    {
        $methods = array_map(
            static fn(ReflectionMethod $method): string => $method->getName(),
            array_filter($reflection->getMethods(ReflectionMethod::IS_PUBLIC), static fn(ReflectionMethod $method): bool => '__construct' !== $method->getName()),
        );
        sort($methods);

        return $methods;
    }
}
