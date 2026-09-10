<?php

declare(strict_types=1);

namespace Kongono\ExternalServiceBindingBuild\Tests\Unit\Artifact;

use Kongono\ExternalServiceBindingBuild\Artifact\ExternalServiceBindingArtifactBuilder;
use Kongono\ExternalServiceBindingBuild\Tests\Support\ExternalServiceBindingSourceFactory;
use PHPUnit\Framework\TestCase;

final class ExternalServiceBindingArtifactTest extends TestCase
{
    public function testValueExposesOnlyTypedDetachedVerifiedEvidence(): void
    {
        $artifact = (new ExternalServiceBindingArtifactBuilder())->build(ExternalServiceBindingSourceFactory::create());
        $bindings = $artifact->bindings();
        array_pop($bindings);
        self::assertCount(1, $artifact->bindings());
        self::assertTrue($artifact->sourceIdentity()->equals(ExternalServiceBindingSourceFactory::create()->identity()));
        self::assertSame(hash('sha256', $artifact->canonicalBytes()), $artifact->artifactSha256());
        self::assertTrue($artifact->source()->equals(ExternalServiceBindingSourceFactory::create()));
    }
}
