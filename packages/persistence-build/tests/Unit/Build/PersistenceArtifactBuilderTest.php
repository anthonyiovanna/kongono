<?php

declare(strict_types=1);

namespace Kongono\PersistenceBuild\Tests\Unit\Build;

use Kongono\PersistenceBuild\Build\PersistenceArtifactBuilder;
use Kongono\PersistenceBuild\Internal\CanonicalJson;
use Kongono\PersistenceBuild\Model\ConstructionKind;
use PHPUnit\Framework\TestCase;

final class PersistenceArtifactBuilderTest extends TestCase
{
    private const SOURCE = 'packages/persistence-build/tests/Fixture/Application/kongono.persistence.json';

    public function testBuildIsDeterministicAndBindsCurrentEvidence(): void
    {
        $root = dirname(__DIR__, 5);
        $builder = new PersistenceArtifactBuilder();
        $first = $builder->build($root, self::SOURCE);
        $second = $builder->build($root, self::SOURCE);

        self::assertSame($first->canonicalBytes(), $second->canonicalBytes());
        self::assertSame($first->artifactSha256(), $second->artifactSha256());
        self::assertSame(2, count($first->model()->entities()));
        self::assertSame(ConstructionKind::StaticFactory, $first->model()->entities()[1]->construction()->kind());
        self::assertStringEndsWith("\n", $first->canonicalBytes());
        self::assertStringNotContainsString("\r", $first->canonicalBytes());
    }

    public function testRawSourceIdentityMakesFormattingChangesExplicitWhileCanonicalSemanticsRemainOrdered(): void
    {
        $root = dirname(__DIR__, 5);
        $artifact = (new PersistenceArtifactBuilder())->build($root, self::SOURCE);
        $decoded = CanonicalJson::decodeObject($artifact->canonicalBytes(), 96);
        $source = $decoded['source'] ?? null;
        self::assertIsArray($source);
        self::assertSame(['schema', 'version', 'model_schema', 'model_version', 'source', 'provenance', 'model'], array_keys($decoded));
        self::assertSame(hash_file('sha256', $root . '/' . self::SOURCE), $source['sha256']);
    }
}
