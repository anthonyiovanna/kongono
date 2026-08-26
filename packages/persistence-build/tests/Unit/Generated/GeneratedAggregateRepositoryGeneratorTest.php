<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Tests\Unit\Generated;

use Kongono\PersistenceBuild\Build\PersistenceArtifactBuilder;
use Kongono\PersistenceBuild\Generated\GeneratedAggregateRepositoryGenerator;
use PHPUnit\Framework\TestCase;

final class GeneratedAggregateRepositoryGeneratorTest extends TestCase
{
    public function testTwoExactGenerationsAreByteIdenticalWithExactMetadata(): void
    {
        $root = dirname(__DIR__, 5);
        $persistenceSource = 'packages/persistence-build/tests/Fixture/Application/kongono.persistence.json';
        $repositorySource = 'packages/persistence-build/tests/Fixture/Application/kongono.persistence-repositories.json';
        $persistence = (new PersistenceArtifactBuilder())->build($root, $persistenceSource);
        $generator = new GeneratedAggregateRepositoryGenerator();
        $first = $generator->generate($root, $repositorySource, $persistence->canonicalBytes());
        $second = $generator->generate($root, $repositorySource, $persistence->canonicalBytes());
        self::assertSame($first->canonicalBytes(), $second->canonicalBytes());
        self::assertSame($first->generatedFileSha256(), $second->generatedFileSha256());
        self::assertSame(['account'], array_keys($first->classMap()));
        self::assertSame(['loadWithProfiles'], $first->classMap()['account']['load_methods']);
        self::assertStringEndsWith("\n", $first->canonicalBytes());
        self::assertStringNotContainsString("\r", $first->canonicalBytes());
        self::assertFalse(str_starts_with($first->canonicalBytes(), "\xEF\xBB\xBF"));
    }
}
