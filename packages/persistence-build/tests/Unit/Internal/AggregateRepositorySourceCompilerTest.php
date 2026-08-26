<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Tests\Unit\Internal;

use Kongono\PersistenceBuild\Build\PersistenceArtifactBuilder;
use Kongono\PersistenceBuild\Exception\InvalidPersistenceSourceException;
use Kongono\PersistenceBuild\Internal\AggregateRepositorySourceCompiler;
use PHPUnit\Framework\TestCase;

final class AggregateRepositorySourceCompilerTest extends TestCase
{
    public function testExactCurrentFixtureCompilesBoundedRepository(): void
    {
        $root = dirname(__DIR__, 5);
        $persistenceSource = 'packages/persistence-build/tests/Fixture/Application/kongono.persistence.json';
        $repositorySource = 'packages/persistence-build/tests/Fixture/Application/kongono.persistence-repositories.json';
        $artifact = (new PersistenceArtifactBuilder())->build($root, $persistenceSource);
        $compiled = (new AggregateRepositorySourceCompiler())->compile($root, $repositorySource, $artifact->canonicalBytes());
        self::assertSame($repositorySource, $compiled['source_path']);
        self::assertCount(1, $compiled['repositories']);
        $repository = $compiled['repositories'][0];
        self::assertSame('account', $repository->aggregate());
        self::assertSame('loadWithProfiles', $repository->loadPlans()[0]->method());
        self::assertSame(33, $repository->maxChanges());
    }

    public function testDuplicateJsonMembersFailBeforeArtifactUse(): void
    {
        $root = sys_get_temp_dir() . '/kongono-repository-source-' . bin2hex(random_bytes(8));
        self::assertTrue(mkdir($root));
        $path = $root . '/kongono.persistence-repositories.json';
        try {
            self::assertGreaterThan(0, file_put_contents($path, '{"schema":"a","schema":"b"}'));
            try {
                (new AggregateRepositorySourceCompiler())->compile($root, 'kongono.persistence-repositories.json', 'invalid');
                self::fail('Expected duplicate-key rejection.');
            } catch (InvalidPersistenceSourceException $failure) {
                self::assertSame('KGN-PERSISTENCE-BUILD-1016', $failure->diagnosticId());
            }
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
            if (is_dir($root)) {
                rmdir($root);
            }
        }
    }
}
