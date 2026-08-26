<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Tests\Unit\Generated;

use Kongono\PersistenceBuild\Build\PersistenceArtifactBuilder;
use Kongono\PersistenceBuild\Exception\InvalidGeneratedPersistenceStateException;
use Kongono\PersistenceBuild\Generated\GeneratedPersistenceStateGenerator;
use PHPUnit\Framework\TestCase;

final class GeneratedPersistenceStateGeneratorTest extends TestCase
{
    private const SOURCE = 'packages/persistence-build/tests/Fixture/Application/kongono.persistence.json';

    public function testGenerationIsExactCurrentDeterministicAndOrdinal(): void
    {
        $root = dirname(__DIR__, 5);
        $artifact = (new PersistenceArtifactBuilder())->build($root, self::SOURCE);
        $generator = new GeneratedPersistenceStateGenerator();
        $first = $generator->generate($root, self::SOURCE, $artifact->canonicalBytes());
        $second = $generator->generate($root, self::SOURCE, $artifact->canonicalBytes());

        self::assertSame($first->canonicalBytes(), $second->canonicalBytes());
        self::assertSame($first->generatedFileSha256(), $second->generatedFileSha256());
        self::assertSame(hash('sha256', $first->canonicalBytes()), $first->generatedFileSha256());
        self::assertSame([
            'Kongono\PersistenceBuild\Tests\Fixture\Domain\Account' => $first->generatedNamespace() . '\EntityState_0001',
            'Kongono\PersistenceBuild\Tests\Fixture\Domain\Profile' => $first->generatedNamespace() . '\EntityState_0002',
        ], $first->classMap());
        self::assertStringEndsWith('\Artifact_' . substr($artifact->artifactSha256(), 0, 32), $first->generatedNamespace());
        self::assertLessThanOrEqual(GeneratedPersistenceStateGenerator::MAX_GENERATED_BYTES, strlen($first->canonicalBytes()));
        self::assertStringNotContainsString(str_replace('\\', '/', $root), $first->canonicalBytes());
    }

    public function testGenerationRejectsAnythingOtherThanTheExactCurrentArtifact(): void
    {
        $root = dirname(__DIR__, 5);
        $bytes = (new PersistenceArtifactBuilder())->build($root, self::SOURCE)->canonicalBytes();

        try {
            (new GeneratedPersistenceStateGenerator())->generate($root, self::SOURCE, $bytes . 'tampered');
            self::fail('Expected exact-current input rejection.');
        } catch (InvalidGeneratedPersistenceStateException $failure) {
            self::assertSame('KGN-PERSISTENCE-BUILD-1011', $failure->diagnosticId());
        }
    }
}
