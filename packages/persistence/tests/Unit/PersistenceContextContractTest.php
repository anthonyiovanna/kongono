<?php

declare(strict_types=1);

namespace Kongono\Persistence\Tests\Unit;

use Kongono\Persistence\ChangeKind;
use Kongono\Persistence\ContextState;
use Kongono\Persistence\EntityState;
use Kongono\Persistence\FlushOutcome;
use Kongono\Persistence\PersistenceContext;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

final class PersistenceContextContractTest extends TestCase
{
    public function testEnumsAndContextSurfaceAreExact(): void
    {
        self::assertSame(['New', 'Managed', 'Removed'], array_map(static fn(EntityState $case): string => $case->name, EntityState::cases()));
        self::assertSame(['Active', 'Flushing', 'Failed', 'Retired'], array_map(static fn(ContextState $case): string => $case->name, ContextState::cases()));
        self::assertSame(['Insert', 'Update', 'Delete'], array_map(static fn(ChangeKind $case): string => $case->name, ChangeKind::cases()));
        self::assertSame(['NoChanges', 'Applied', 'EntityAbsent', 'OptimisticConflict'], array_map(static fn(FlushOutcome $case): string => $case->name, FlushOutcome::cases()));

        $context = new ReflectionClass(PersistenceContext::class);
        self::assertTrue($context->isInterface());
        self::assertSame(
            ['changes', 'entityForIdentity', 'entityState', 'identityOf', 'retire', 'state'],
            $this->declaredMethodNames($context),
        );

        $signatures = [];
        foreach ($context->getMethods() as $method) {
            $signatures[$method->getName()] = [
                array_map(static fn(\ReflectionParameter $parameter): string => (string) $parameter->getType(), $method->getParameters()),
                (string) $method->getReturnType(),
            ];
        }
        self::assertSame([
            'state' => [[], ContextState::class],
            'identityOf' => [['object'], '?Kongono\\Persistence\\EntityIdentity'],
            'entityForIdentity' => [['Kongono\\Persistence\\EntityIdentity'], '?object'],
            'entityState' => [['object'], '?Kongono\\Persistence\\EntityState'],
            'changes' => [[], 'Kongono\\Persistence\\ChangeSet'],
            'retire' => [[], 'void'],
        ], $signatures);

        foreach (['flush', 'persist', 'remove', 'find', 'attach', 'detach', 'clear', 'beginTransaction', 'execute'] as $forbidden) {
            self::assertFalse($context->hasMethod($forbidden));
        }

        self::assertContains(
            PersistenceContext::class,
            (new ReflectionClass(\Kongono\Persistence\OperationPersistenceContext::class))->getInterfaceNames(),
        );
    }

    /**
     * @param ReflectionClass<PersistenceContext> $reflection
     * @return list<string>
     */
    private function declaredMethodNames(ReflectionClass $reflection): array
    {
        $names = array_map(static fn(ReflectionMethod $method): string => $method->getName(), $reflection->getMethods());
        sort($names);

        return $names;
    }
}
