<?php

declare(strict_types=1);

namespace Kongono\Persistence\Tests\Unit;

use Kongono\Persistence\ChangeExecutionOutcome;
use Kongono\Persistence\ChangeKind;
use Kongono\Persistence\ContextState;
use Kongono\Persistence\EntityState;
use Kongono\Persistence\Exception\InvalidPersistenceValueException;
use Kongono\Persistence\FlushOutcome;
use Kongono\Persistence\OperationPersistenceContext;
use Kongono\Persistence\Tests\Fixture\Domain\TestEntity;
use Kongono\Persistence\Tests\Fixture\RecordingChangeExecutor;
use Kongono\Persistence\Tests\Fixture\RecordingDatabaseAuthority;
use Kongono\Persistence\Tests\Fixture\TestStateAdapter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OperationPersistenceFlushTest extends TestCase
{
    public function testUnchangedManagedContextReturnsNoChangesWithoutExecutor(): void
    {
        $context = OperationPersistenceContext::forManaged(new TestStateAdapter(), new TestEntity(1, 'Ada', 3));
        $executor = new RecordingChangeExecutor();

        $result = $context->flush(new RecordingDatabaseAuthority(), $executor);

        self::assertSame(FlushOutcome::NoChanges, $result->outcome());
        self::assertSame([0, 0, 0, 0], [$result->attemptedCount(), $result->appliedCount(), $result->absentCount(), $result->conflictCount()]);
        self::assertSame(0, $executor->calls);
        self::assertSame(ContextState::Active, $context->state());
    }

    public function testManagedUpdateExecutesOnceAndRefreshesSnapshotOnlyAfterApplied(): void
    {
        $entity = new TestEntity(2, 'before', 7);
        $adapter = new TestStateAdapter();
        $context = OperationPersistenceContext::forManaged($adapter, $entity);
        $entity->name = 'after';
        $entity->version = 8;
        $database = new RecordingDatabaseAuthority();
        $executor = new RecordingChangeExecutor();

        $result = $context->flush($database, $executor);

        self::assertSame(FlushOutcome::Applied, $result->outcome());
        self::assertSame(1, $executor->calls);
        self::assertSame($database, $executor->database);
        self::assertNotNull($executor->change);
        self::assertSame(ChangeKind::Update, $executor->change->kind());
        self::assertSame(['name', 'version'], $executor->change->changedFields());
        self::assertSame('7', $executor->change->expectedConcurrencyToken()?->value());
        self::assertSame(2, $adapter->snapshotCalls);
        self::assertTrue($context->changes()->isEmpty());
        self::assertSame(EntityState::Managed, $context->entityState($entity));
        self::assertSame(array_fill_keys(array_keys($database->counters()), 0), $database->counters());
    }

    public function testNewInsertBecomesManagedOnlyAfterApplied(): void
    {
        $entity = new TestEntity(3, 'new', 1);
        $context = OperationPersistenceContext::forNew(new TestStateAdapter(), $entity);
        $executor = new RecordingChangeExecutor();

        $result = $context->flush(new RecordingDatabaseAuthority(), $executor);

        self::assertSame(FlushOutcome::Applied, $result->outcome());
        self::assertSame(ChangeKind::Insert, $executor->change?->kind());
        self::assertSame(EntityState::Managed, $context->entityState($entity));
        self::assertTrue($context->changes()->isEmpty());
    }

    public function testAppliedDeletionReleasesAndRetires(): void
    {
        $entity = new TestEntity(4, 'remove', 2);
        $context = OperationPersistenceContext::forManaged(new TestStateAdapter(), $entity);
        $context->markRemoved();
        $executor = new RecordingChangeExecutor();

        $result = $context->flush(new RecordingDatabaseAuthority(), $executor);

        self::assertSame(FlushOutcome::Applied, $result->outcome());
        self::assertSame(ChangeKind::Delete, $executor->change?->kind());
        self::assertSame(ContextState::Retired, $context->state());
    }

    #[DataProvider('zeroAppliedOutcomes')]
    public function testZeroAppliedOutcomesRemainDistinctAndPreserveState(ChangeExecutionOutcome $execution, FlushOutcome $flush): void
    {
        $entity = new TestEntity(5, 'before', 3);
        $adapter = new TestStateAdapter();
        $context = OperationPersistenceContext::forManaged($adapter, $entity);
        $entity->name = 'after';
        $executor = new RecordingChangeExecutor($execution);

        $first = $context->flush(new RecordingDatabaseAuthority(), $executor);
        $second = $context->flush(new RecordingDatabaseAuthority(), $executor);

        self::assertSame($flush, $first->outcome());
        self::assertSame($flush, $second->outcome());
        self::assertSame(ContextState::Active, $context->state());
        self::assertSame(EntityState::Managed, $context->entityState($entity));
        self::assertSame(2, $executor->calls);
        self::assertSame(1, $adapter->snapshotCalls);
    }

    /** @return iterable<string, array{ChangeExecutionOutcome, FlushOutcome}> */
    public static function zeroAppliedOutcomes(): iterable
    {
        yield 'absent' => [ChangeExecutionOutcome::EntityAbsent, FlushOutcome::EntityAbsent];
        yield 'conflict' => [ChangeExecutionOutcome::OptimisticConflict, FlushOutcome::OptimisticConflict];
    }

    #[DataProvider('invalidModes')]
    public function testMalformedEvidenceFailsBeforeDatabaseAuthority(string $mode): void
    {
        $entity = new TestEntity(6, 'before', 1);
        $adapter = new TestStateAdapter();
        $context = OperationPersistenceContext::forManaged($adapter, $entity);
        $entity->name = 'after';
        $adapter->mode = $mode;
        $executor = new RecordingChangeExecutor();

        try {
            $context->flush(new RecordingDatabaseAuthority(), $executor);
            self::fail('Malformed evidence was accepted.');
        } catch (InvalidPersistenceValueException) {
            self::assertSame(0, $executor->calls);
            self::assertSame(ContextState::Active, $context->state());
        }
    }

    /** @return iterable<string, array{string}> */
    public static function invalidModes(): iterable
    {
        yield 'multiple' => ['multiple'];
        yield 'wrong entity' => ['wrong_entity'];
        yield 'wrong kind' => ['wrong_kind'];
        yield 'wrong identity' => ['wrong_identity'];
    }

    public function testEntityMethodsRetirementAndDestructionNeverFlushAutomatically(): void
    {
        $entity = new TestEntity(7, 'before', 1);
        $context = OperationPersistenceContext::forManaged(new TestStateAdapter(), $entity);
        $executor = new RecordingChangeExecutor();
        $entity->name = 'after';
        $entity->unrelatedMethod();
        $context->retire();
        unset($context);

        self::assertSame(1, $entity->unrelatedMethodCalls);
        self::assertSame(0, $executor->calls);
    }
}
