<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Tests\Unit\Internal;

use Kongono\PersistenceBuild\Build\PersistenceArtifactBuilder;
use Kongono\PersistenceBuild\Generated\GeneratedAggregateRepositoryGenerator;
use PHPUnit\Framework\TestCase;

final class GeneratedAggregateRepositoryEmitterTest extends TestCase
{
    public function testEmitterProducesConcreteStatelessRepositoryWithoutMagicQueries(): void
    {
        $root = dirname(__DIR__, 5);
        $persistenceSource = 'packages/persistence-build/tests/Fixture/Application/kongono.persistence.json';
        $repositorySource = 'packages/persistence-build/tests/Fixture/Application/kongono.persistence-repositories.json';
        $artifact = (new PersistenceArtifactBuilder())->build($root, $persistenceSource);
        $bytes = (new GeneratedAggregateRepositoryGenerator())->generate($root, $repositorySource, $artifact->canonicalBytes())->canonicalBytes();
        self::assertStringContainsString('final class AccountAggregateRepository implements AggregateRepository', $bytes);
        self::assertStringContainsString('public function loadWithProfiles(', $bytes);
        self::assertStringContainsString('public function persist(', $bytes);
        self::assertStringContainsString('public function remove(', $bytes);
        self::assertSame(0, preg_match('/__call|findBy|findOneBy|criteria|Reflection|eval\s*\(|beginTransaction|commit\s*\(|rollback\s*\(|close\s*\(/i', $bytes));
        self::assertStringNotContainsString('private $', $bytes);
    }
}
