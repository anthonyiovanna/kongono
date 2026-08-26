<?php

declare(strict_types=1);

namespace Kongono\Persistence\Tests\Unit;

use Kongono\Persistence\ContextState;
use Kongono\Persistence\Exception\InvalidPersistenceStateException;
use Kongono\Persistence\Exception\PersistenceUncertainStateException;
use Kongono\Persistence\OperationPersistenceContext;
use Kongono\Persistence\Tests\Fixture\Domain\TestEntity;
use Kongono\Persistence\Tests\Fixture\RecordingChangeExecutor;
use Kongono\Persistence\Tests\Fixture\RecordingDatabaseAuthority;
use Kongono\Persistence\Tests\Fixture\TestStateAdapter;
use PHPUnit\Framework\TestCase;
use WeakReference;

final class OperationPersistenceFailureTest extends TestCase
{
    public function testExecutorFailureIsTerminalReleasedNonDisclosingAndNeverRetried(): void
    {
        $entity = new TestEntity(31, 'entity-secret', 4);
        $adapter = new TestStateAdapter();
        $context = OperationPersistenceContext::forManaged($adapter, $entity);
        $entity->name = 'changed-secret';
        $adapterReference = WeakReference::create($adapter);
        $database = new RecordingDatabaseAuthority();
        $executor = new RecordingChangeExecutor();
        $executor->throw = true;

        try {
            $context->flush($database, $executor);
            self::fail('Executor failure was accepted.');
        } catch (PersistenceUncertainStateException $exception) {
            self::assertSame(ContextState::Failed, $context->state());
            self::assertSame(1, $executor->calls);
            self::assertNotNull($exception->getPrevious());
            $presentation = $exception->getMessage() . ' ' . (string) $exception . ' ' . $this->contextText($exception);
            foreach (['entity-secret', 'changed-secret', 'entity-31', 'sql=secret', 'parameter=secret', 'synthetic-provider-message'] as $private) {
                self::assertStringNotContainsString($private, $presentation);
            }
            self::assertSame(array_fill_keys(array_keys($database->counters()), 0), $database->counters());
        }

        $executor->change = null;
        $executor->database = null;
        unset($entity, $adapter);
        gc_collect_cycles();
        self::assertNull($adapterReference->get());
        $this->assertContextReferencesReleased($context);
        $this->assertLaterUseRejected($context);
        $context->retire();
        $context->retire();
        self::assertSame(ContextState::Retired, $context->state());
    }

    public function testPostAppliedIdentityFailureIsTerminalAndNeverReportsApplied(): void
    {
        $entity = new TestEntity(32, 'new-secret', 1);
        $adapter = new TestStateAdapter();
        $adapter->throwIdentityOnCall = 2;
        $context = OperationPersistenceContext::forNew($adapter, $entity);
        $executor = new RecordingChangeExecutor();

        try {
            $context->flush(new RecordingDatabaseAuthority(), $executor);
            self::fail('Post-apply identity failure was accepted.');
        } catch (PersistenceUncertainStateException $exception) {
            self::assertSame(ContextState::Failed, $context->state());
            self::assertSame(1, $executor->calls);
            self::assertStringNotContainsString('synthetic-provider-identity-secret', (string) $exception);
        }
    }

    public function testPostAppliedSnapshotFailureIsTerminal(): void
    {
        $entity = new TestEntity(33, 'before', 1);
        $adapter = new TestStateAdapter();
        $adapter->throwSnapshotOnCall = 2;
        $context = OperationPersistenceContext::forManaged($adapter, $entity);
        $entity->name = 'after';

        $this->expectException(PersistenceUncertainStateException::class);
        try {
            $context->flush(new RecordingDatabaseAuthority(), new RecordingChangeExecutor());
        } finally {
            self::assertSame(ContextState::Failed, $context->state());
        }
    }

    private function assertLaterUseRejected(OperationPersistenceContext $context): void
    {
        try {
            $context->changes();
            self::fail('Failed context remained usable.');
        } catch (InvalidPersistenceStateException) {
            self::assertSame(ContextState::Failed, $context->state());
        }
    }

    private function contextText(PersistenceUncertainStateException $exception): string
    {
        return implode(' ', array_map(
            static fn($field): string => $field->key() . '=' . $field->value(),
            $exception->diagnosticContext()->fields(),
        ));
    }

    private function assertContextReferencesReleased(OperationPersistenceContext $context): void
    {
        $contextReflection = new \ReflectionObject($context);
        $tracked = $contextReflection->getProperty('tracked')->getValue($context);
        self::assertIsObject($tracked);
        foreach ((new \ReflectionObject($tracked))->getProperties() as $property) {
            self::assertNull($property->getValue($tracked), $property->getName());
        }
    }
}
