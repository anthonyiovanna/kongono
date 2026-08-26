<?php

declare(strict_types=1);

namespace Kongono\Persistence\Tests\Unit;

use Kongono\Persistence\ChangeKind;
use Kongono\Persistence\ContextState;
use Kongono\Persistence\EntityState;
use Kongono\Persistence\Exception\InvalidPersistenceStateException;
use Kongono\Persistence\Exception\InvalidPersistenceValueException;
use Kongono\Persistence\OperationPersistenceContext;
use Kongono\Persistence\Tests\Fixture\Domain\TestEntity;
use Kongono\Persistence\Tests\Fixture\TestStateAdapter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OperationPersistenceContextTest extends TestCase
{
    public function testManagedContextObservesOnlyTheExactEntityAndIdentity(): void
    {
        $entity = new TestEntity(7, 'Ada', 3);
        $adapter = new TestStateAdapter();
        $context = OperationPersistenceContext::forManaged($adapter, $entity);
        $identity = $context->identityOf($entity);

        self::assertSame(ContextState::Active, $context->state());
        self::assertNotNull($identity);
        self::assertSame($identity, $context->identityOf($entity));
        self::assertSame($entity, $context->entityForIdentity($identity));
        self::assertSame(EntityState::Managed, $context->entityState($entity));
        self::assertNull($context->identityOf(new TestEntity(7, 'Ada', 3)));
        self::assertNull($context->entityState(new TestEntity(7, 'Ada', 3)));
        self::assertNull($context->entityForIdentity(new \Kongono\Persistence\EntityIdentity(TestEntity::class, 'entity-8')));
        self::assertTrue($context->changes()->isEmpty());
    }

    public function testNewAndRemovedStatesAreExplicitWithoutIo(): void
    {
        $entity = new TestEntity(8, 'Grace', 1);
        $context = OperationPersistenceContext::forNew(new TestStateAdapter(), $entity);

        self::assertSame(EntityState::New, $context->entityState($entity));
        self::assertSame(ChangeKind::Insert, $context->changes()->changes()[0]->kind());
        $this->expectException(InvalidPersistenceStateException::class);
        $context->markRemoved();
    }

    public function testManagedRemovalChangesOnlyContextState(): void
    {
        $entity = new TestEntity(9, 'Lin', 2);
        $context = OperationPersistenceContext::forManaged(new TestStateAdapter(), $entity);

        $context->markRemoved();

        self::assertSame(EntityState::Removed, $context->entityState($entity));
        self::assertSame(ChangeKind::Delete, $context->changes()->changes()[0]->kind());
        $this->expectException(InvalidPersistenceStateException::class);
        $context->markRemoved();
    }

    #[DataProvider('invalidAdapterModes')]
    public function testMalformedAdapterEvidenceFailsWhileContextRemainsActive(string $mode): void
    {
        $entity = new TestEntity(10, 'Edsger', 1);
        $adapter = new TestStateAdapter();
        $context = OperationPersistenceContext::forManaged($adapter, $entity);
        $entity->name = 'changed';
        $adapter->mode = $mode;

        try {
            $context->changes();
            self::fail('Malformed evidence was accepted.');
        } catch (InvalidPersistenceValueException) {
            self::assertSame(ContextState::Active, $context->state());
        }
    }

    /** @return iterable<string, array{string}> */
    public static function invalidAdapterModes(): iterable
    {
        yield 'multiple' => ['multiple'];
        yield 'wrong entity' => ['wrong_entity'];
        yield 'wrong kind' => ['wrong_kind'];
        yield 'wrong identity' => ['wrong_identity'];
    }

    public function testFactoryRejectsWrongExactAdapterEntityTypeWithoutRetainingEntity(): void
    {
        $adapter = new TestStateAdapter();
        $adapter->declaredEntityType = \stdClass::class;

        $this->expectException(InvalidPersistenceValueException::class);
        OperationPersistenceContext::forManaged($adapter, new TestEntity(11, 'Barbara', 1));
    }

    public function testRetirementIsIdempotentAndRejectsEveryLaterOperationExceptState(): void
    {
        $entity = new TestEntity(12, 'Margaret', 1);
        $context = OperationPersistenceContext::forManaged(new TestStateAdapter(), $entity);
        $context->retire();
        $context->retire();

        self::assertSame(ContextState::Retired, $context->state());
        $this->expectException(InvalidPersistenceStateException::class);
        $context->identityOf($entity);
    }
}
