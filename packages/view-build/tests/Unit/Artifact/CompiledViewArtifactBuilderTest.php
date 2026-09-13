<?php

declare(strict_types=1);

namespace Kongono\ViewBuild\Tests\Unit\Artifact;

use Kongono\ViewBuild\Tests\Fixture\BuildProbe;
use PHPUnit\Framework\TestCase;

final class CompiledViewArtifactBuilderTest extends TestCase
{
    public function testBuildProducesDistinctRelatedCanonicalIdentities(): void
    {
        $artifact = BuildProbe::artifact();
        self::assertSame(hash('sha256', $artifact->metadataBytes()), $artifact->compiledViewIdentity()->value());
        self::assertSame(hash('sha256', $artifact->generatedPhpBytes()), $artifact->generatedPhpSha256());
        self::assertSame(2, $artifact->viewCount());
        self::assertSame('Kongono\\Generated\\View\\CompiledViewSet_' . $artifact->modelSha256(), $artifact->generatedClass());
        self::assertTrue($artifact->equals(BuildProbe::artifact()));
        $metadata = json_decode($artifact->metadataBytes(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($metadata);
        self::assertSame(['schema', 'artifact_version', 'contract_version', 'builder', 'source_sha256', 'model_sha256', 'generated_php', 'views'], array_keys($metadata));
    }
}
