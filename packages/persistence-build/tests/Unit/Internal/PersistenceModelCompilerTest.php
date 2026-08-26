<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Tests\Unit\Internal;

use Kongono\DatabaseMapping\Build\MappingArtifactBuilder;
use Kongono\PersistenceBuild\Diagnostic\PersistenceBuildDiagnostic;
use Kongono\PersistenceBuild\Exception\InvalidPersistenceSourceException;
use Kongono\PersistenceBuild\Internal\CanonicalJson;
use Kongono\PersistenceBuild\Internal\PersistenceModelCompiler;
use PHPUnit\Framework\TestCase;

final class PersistenceModelCompilerTest extends TestCase
{
    public function testDuplicateAggregateMembershipFailsWithAggregateDiagnostic(): void
    {
        $root = dirname(__DIR__, 5);
        $source = CanonicalJson::decodeObject((string) file_get_contents($root . '/packages/persistence-build/tests/Fixture/Application/kongono.persistence.json'), 32);
        $aggregates = $source['aggregates'] ?? null;
        self::assertIsArray($aggregates);
        $first = $aggregates[0] ?? null;
        self::assertIsArray($first);
        $aggregates[] = $first;
        $source['aggregates'] = $aggregates;
        $mapping = (new MappingArtifactBuilder())->build($root, 'packages/persistence-build/tests/Fixture/Application/kongono.data-mapping.json');
        $mappingData = CanonicalJson::decodeObject($mapping->canonicalBytes(), 64);

        try {
            (new PersistenceModelCompiler())->compile($root, $source, $mappingData);
            self::fail('Expected aggregate rejection.');
        } catch (InvalidPersistenceSourceException $failure) {
            self::assertSame(PersistenceBuildDiagnostic::InvalidAggregate->value, $failure->diagnosticId());
        }
    }

    public function testRelationshipOrderingAndOwnershipCompileAsDeclarationsOnly(): void
    {
        $root = dirname(__DIR__, 5);
        $source = CanonicalJson::decodeObject((string) file_get_contents($root . '/packages/persistence-build/tests/Fixture/Application/kongono.persistence.json'), 32);
        $mapping = (new MappingArtifactBuilder())->build($root, 'packages/persistence-build/tests/Fixture/Application/kongono.data-mapping.json');
        $mappingData = CanonicalJson::decodeObject($mapping->canonicalBytes(), 64);
        $compiled = (new PersistenceModelCompiler())->compile($root, $source, $mappingData);

        self::assertSame('aggregate', $compiled['model']->relationships()[0]->ownership()->value);
        self::assertSame('ascending', $compiled['model']->relationships()[0]->order()[0]->direction()->value);
    }
}
