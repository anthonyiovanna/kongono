<?php

declare(strict_types=1);

namespace Kongono\Persistence\Tests\Unit;

use Kongono\Persistence\ContextState;
use Kongono\Persistence\Exception\PersistenceUncertainStateException;
use Kongono\Persistence\OperationPersistenceContext;
use Kongono\Persistence\Tests\Fixture\Domain\TestEntity;
use Kongono\Persistence\Tests\Fixture\RecordingChangeExecutor;
use Kongono\Persistence\Tests\Fixture\RecordingDatabaseAuthority;
use Kongono\Persistence\Tests\Fixture\TestStateAdapter;
use PHPUnit\Framework\TestCase;
use WeakReference;

final class OperationPersistenceIsolationTest extends TestCase
{
    public function testTwoContextsShareNoOperationStateOrAuthority(): void
    {
        $firstEntity = new TestEntity(1, 'first', 1);
        $secondEntity = new TestEntity(2, 'second', 1);
        $firstAdapter = new TestStateAdapter();
        $secondAdapter = new TestStateAdapter();
        $first = OperationPersistenceContext::forManaged($firstAdapter, $firstEntity);
        $second = OperationPersistenceContext::forManaged($secondAdapter, $secondEntity);
        $firstEntity->name = 'first changed';
        $secondEntity->name = 'second changed';
        $firstDatabase = new RecordingDatabaseAuthority();
        $secondDatabase = new RecordingDatabaseAuthority();
        $firstExecutor = new RecordingChangeExecutor();
        $secondExecutor = new RecordingChangeExecutor();

        $first->flush($firstDatabase, $firstExecutor);

        self::assertSame($firstDatabase, $firstExecutor->database);
        self::assertNull($secondExecutor->database);
        self::assertFalse($second->changes()->isEmpty());
        self::assertSame(ContextState::Active, $second->state());
        self::assertNull($first->identityOf($secondEntity));
        self::assertNull($second->identityOf($firstEntity));
        self::assertNotSame($firstAdapter, $secondAdapter);
        self::assertNotSame($firstDatabase, $secondDatabase);
        self::assertNotSame($firstExecutor, $secondExecutor);
    }

    public function testFailingOneContextCannotAffectAnotherAndBothReleaseReferences(): void
    {
        $failedEntity = new TestEntity(3, 'failed', 1);
        $activeEntity = new TestEntity(4, 'active', 1);
        $failedAdapter = new TestStateAdapter();
        $activeAdapter = new TestStateAdapter();
        $failed = OperationPersistenceContext::forManaged($failedAdapter, $failedEntity);
        $active = OperationPersistenceContext::forManaged($activeAdapter, $activeEntity);
        $failedEntity->name = 'changed';
        $executor = new RecordingChangeExecutor();
        $executor->throw = true;

        try {
            $failed->flush(new RecordingDatabaseAuthority(), $executor);
            self::fail('Expected uncertain failure.');
        } catch (PersistenceUncertainStateException) {
            self::assertSame(ContextState::Failed, $failed->state());
            self::assertSame(ContextState::Active, $active->state());
        }

        $failedReference = WeakReference::create($failedEntity);
        $activeReference = WeakReference::create($activeEntity);
        $executor->change = null;
        $executor->database = null;
        $active->retire();
        unset($failedEntity, $activeEntity, $failedAdapter, $activeAdapter);
        gc_collect_cycles();
        self::assertNull($failedReference->get());
        self::assertNull($activeReference->get());
        self::assertSame(ContextState::Retired, $active->state());
    }
}
