<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Tests\Unit\Generated;

use Kongono\PersistenceBuild\Build\PersistenceArtifactBuilder;
use Kongono\PersistenceBuild\Exception\InvalidPersistenceSourceException;
use Kongono\PersistenceBuild\Generated\GeneratedAggregateRepositoryGenerator;
use Kongono\PersistenceBuild\Generated\GeneratedAggregateRepositoryValidator;
use PHPUnit\Framework\TestCase;

final class GeneratedAggregateRepositoryValidatorTest extends TestCase
{
    public function testStructureAndCurrentValidationDoNotExecuteGeneratedBytes(): void
    {
        $root = dirname(__DIR__, 5);
        $persistenceSource = 'packages/persistence-build/tests/Fixture/Application/kongono.persistence.json';
        $repositorySource = 'packages/persistence-build/tests/Fixture/Application/kongono.persistence-repositories.json';
        $persistence = (new PersistenceArtifactBuilder())->build($root, $persistenceSource);
        $generated = (new GeneratedAggregateRepositoryGenerator())->generate($root, $repositorySource, $persistence->canonicalBytes());
        $class = $generated->classMap()['account']['repository'];
        $loadedBefore = class_exists($class, false);
        $validator = new GeneratedAggregateRepositoryValidator();
        $validator->validateStructure($generated->canonicalBytes());
        $validator->validateCurrent($root, $repositorySource, $persistence->canonicalBytes(), $generated->canonicalBytes());
        self::assertSame($loadedBefore, class_exists($class, false));
    }

    public function testSemanticallyCoherentByteChangeIsRejectedAsStale(): void
    {
        $root = dirname(__DIR__, 5);
        $persistenceSource = 'packages/persistence-build/tests/Fixture/Application/kongono.persistence.json';
        $repositorySource = 'packages/persistence-build/tests/Fixture/Application/kongono.persistence-repositories.json';
        $persistence = (new PersistenceArtifactBuilder())->build($root, $persistenceSource);
        $generated = (new GeneratedAggregateRepositoryGenerator())->generate($root, $repositorySource, $persistence->canonicalBytes());
        $stale = str_replace('loadWithProfiles', 'loadWithProfilez', $generated->canonicalBytes());
        (new GeneratedAggregateRepositoryValidator())->validateStructure($stale);
        try {
            (new GeneratedAggregateRepositoryValidator())->validateCurrent($root, $repositorySource, $persistence->canonicalBytes(), $stale);
            self::fail('Expected stale repository rejection.');
        } catch (InvalidPersistenceSourceException $failure) {
            self::assertSame('KGN-PERSISTENCE-BUILD-1018', $failure->diagnosticId());
        }
    }
}
