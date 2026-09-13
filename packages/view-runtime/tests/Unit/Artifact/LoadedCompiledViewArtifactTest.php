<?php

declare(strict_types=1);

namespace Kongono\ViewRuntime\Tests\Unit\Artifact;

use Kongono\ViewRuntime\Artifact\CompiledViewArtifactLoader;
use Kongono\ViewRuntime\Artifact\LoadedCompiledViewArtifact;
use Kongono\ViewRuntime\Tests\Fixture\RuntimeProbe;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class LoadedCompiledViewArtifactTest extends TestCase
{
    public function testHandleCannotBePubliclyConstructedAndCarriesExactEvidence(): void
    {
        self::assertTrue((new ReflectionClass(LoadedCompiledViewArtifact::class))->getConstructor()?->isPrivate());
        $fixture = RuntimeProbe::publish();
        try {
            $loaded = (new CompiledViewArtifactLoader())->load($fixture['root'], $fixture['directory'], $fixture['artifact']->compiledViewIdentity());
            self::assertSame($fixture['artifact']->sourceIdentity()->value(), $loaded->sourceSha256());
            self::assertSame($fixture['artifact']->modelSha256(), $loaded->modelSha256());
            self::assertSame($fixture['artifact']->generatedPhpSha256(), $loaded->generatedPhpSha256());
        } finally {
            RuntimeProbe::cleanup($fixture);
        }
    }
}
