<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Tests\Unit\Generated;

use Kongono\Persistence\CascadeAction;
use Kongono\Persistence\EntityIdentity;
use Kongono\Persistence\Exception\InvalidAggregatePersistenceException;
use Kongono\Persistence\FlushOutcome;
use Kongono\Persistence\FlushResult;
use Kongono\Persistence\LoadedAggregate;
use Kongono\Persistence\Tests\Fixture\RecordingAggregateCascadeExecutor;
use Kongono\Persistence\Tests\Fixture\RecordingAggregateLoadExecutor;
use Kongono\Persistence\Tests\Fixture\RecordingDatabaseAuthority;
use Kongono\PersistenceBuild\Build\PersistenceArtifactBuilder;
use Kongono\PersistenceBuild\Generated\GeneratedAggregateRepositoryGenerator;
use Kongono\PersistenceBuild\Tests\Fixture\Domain\Account;
use Kongono\PersistenceBuild\Tests\Fixture\Domain\Profile;
use PHPUnit\Framework\TestCase;
use WeakReference;

final class GeneratedAggregateRepositoryAcceptanceTest extends TestCase
{
    public function testNamedLoadAndExplicitCascadesForwardExactCapabilitiesOnce(): void
    {
        [$repository, $path] = $this->repository();
        try {
            $database = new RecordingDatabaseAuthority();
            $identity = new EntityIdentity(Account::class, 'i:7');
            $root = new Account(7, 'Ada', 3);
            $profiles = [Profile::restore(8, 7, 'Ada'), Profile::restore(9, 7, 'Grace')];
            $loaded = new LoadedAggregate($root, ['account_profiles' => $profiles]);
            $weakRoot = WeakReference::create($root);
            $loadExecutor = new RecordingAggregateLoadExecutor($loaded);
            $load = new \ReflectionMethod($repository, 'loadWithProfiles');
            self::assertSame($loaded, $load->invoke($repository, $identity, $database, $loadExecutor));
            self::assertSame(1, $loadExecutor->calls);
            self::assertSame($database, $loadExecutor->database);
            self::assertSame($identity, $loadExecutor->identity);
            self::assertSame('loadWithProfiles', $loadExecutor->plan?->methodName());
            self::assertSame($profiles, $loaded->relationship('account_profiles'));

            $cascadeExecutor = new RecordingAggregateCascadeExecutor(new FlushResult(FlushOutcome::Applied, 1, 1, 0, 0));
            $persisted = (new \ReflectionMethod($repository, 'persist'))->invoke($repository, $loaded, $database, $cascadeExecutor);
            self::assertInstanceOf(FlushResult::class, $persisted);
            self::assertSame(FlushOutcome::Applied, $persisted->outcome());
            self::assertSame(1, $cascadeExecutor->calls);
            self::assertSame(CascadeAction::Persist, $cascadeExecutor->action);
            self::assertSame($database, $cascadeExecutor->database);
            self::assertSame($loaded, $cascadeExecutor->aggregate);

            $removed = (new \ReflectionMethod($repository, 'remove'))->invoke($repository, $loaded, $database, $cascadeExecutor);
            self::assertInstanceOf(FlushResult::class, $removed);
            self::assertSame(FlushOutcome::Applied, $removed->outcome());
            self::assertSame(2, $cascadeExecutor->calls);
            self::assertSame(CascadeAction::Remove, $cascadeExecutor->action);
            self::assertSame(array_fill_keys(array_keys($database->counters()), 0), $database->counters());
            unset($cascadeExecutor, $loadExecutor, $loaded, $profiles, $root, $repository);
            gc_collect_cycles();
            self::assertNull($weakRoot->get());
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testAbsenceAndInvalidShapeRemainDistinctWithoutRetry(): void
    {
        [$repository, $path] = $this->repository();
        try {
            $database = new RecordingDatabaseAuthority();
            $identity = new EntityIdentity(Account::class, 'i:7');
            $absent = new RecordingAggregateLoadExecutor(null);
            $load = new \ReflectionMethod($repository, 'loadWithProfiles');
            self::assertNull($load->invoke($repository, $identity, $database, $absent));
            self::assertSame(1, $absent->calls);

            $invalid = new RecordingAggregateLoadExecutor(new LoadedAggregate(new Account(7, 'Ada', 3), ['account_profiles' => [new \stdClass()]]));
            try {
                $load->invoke($repository, $identity, $database, $invalid);
                self::fail('Expected exact target-type rejection.');
            } catch (InvalidAggregatePersistenceException $failure) {
                self::assertSame('KGN-PERSISTENCE-1009', $failure->diagnosticId());
                self::assertSame(1, $invalid->calls);
            }
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    /** @return array{object, string} */
    private function repository(): array
    {
        $root = dirname(__DIR__, 5);
        $persistenceSource = 'packages/persistence-build/tests/Fixture/Application/kongono.persistence.json';
        $repositorySource = 'packages/persistence-build/tests/Fixture/Application/kongono.persistence-repositories.json';
        $persistence = (new PersistenceArtifactBuilder())->build($root, $persistenceSource);
        $generated = (new GeneratedAggregateRepositoryGenerator())->generate($root, $repositorySource, $persistence->canonicalBytes());
        $path = sys_get_temp_dir() . '/kongono-aggregate-repository-' . bin2hex(random_bytes(8)) . '.php';
        self::assertSame(strlen($generated->canonicalBytes()), file_put_contents($path, $generated->canonicalBytes()));
        $class = $generated->classMap()['account']['repository'];
        if (!class_exists($class, false)) {
            require $path;
        }

        return [new $class(), $path];
    }
}
