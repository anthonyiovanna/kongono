<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Tests\Unit\Generated;

use Kongono\ExternalServiceBindingBuild\Artifact\ExternalServiceBindingArtifactBuilder;
use Kongono\ExternalServiceBindingBuild\Generated\GeneratedExternalServiceBindingsGenerator;
use Kongono\ExternalServiceBindingBuild\Tests\Support\ExternalServiceBindingSourceFactory;
use PHPUnit\Framework\TestCase;

final class GeneratedExternalServiceBindingsTest extends TestCase
{
    public function testValueCarriesDetachedProvenanceAndCanonicalBytes(): void
    {
        $artifact = (new ExternalServiceBindingArtifactBuilder())->build(ExternalServiceBindingSourceFactory::create());
        $generated = (new GeneratedExternalServiceBindingsGenerator())->generate($artifact);
        $bindings = $generated->bindings();
        array_pop($bindings);
        self::assertCount(1, $generated->bindings());
        self::assertSame(hash('sha256', $generated->canonicalBytes()), $generated->generatedPhpSha256());
        self::assertSame($artifact->sourceSha256(), $generated->sourceSha256());
        self::assertSame($artifact->artifactSha256(), $generated->bindingArtifactSha256());
    }
}
