<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphArtifact\Tests\Unit\Artifact;

use Kongono\ApplicationGraphArtifact\Artifact\ApplicationGraphArtifact;
use Kongono\ApplicationGraphArtifact\Artifact\ApplicationGraphArtifactBuilder;
use Kongono\ApplicationGraphBuild\Build\ApplicationGraphBuilder;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ApplicationGraphArtifactBuilderTest extends TestCase
{
    public function testEmptyGraphBuildIsCanonicalFreshAndStateless(): void
    {
        $graph = (new ApplicationGraphBuilder())->build([], []);
        $builder = new ApplicationGraphArtifactBuilder();
        $first = $builder->build($graph);
        $second = $builder->build($graph);
        self::assertNotSame($first, $second);
        self::assertSame($first->canonicalBytes(), $second->canonicalBytes());
        self::assertSame(hash('sha256', $first->canonicalBytes()), $first->artifactSha256());
        self::assertSame(0, $first->nodeCount());
        self::assertSame(0, $first->relationCount());
        self::assertStringNotContainsString('implementation_sha256', $first->canonicalBytes());
        $reflection = new ReflectionClass(ApplicationGraphArtifact::class);
        self::assertTrue($reflection->isFinal());
        self::assertTrue($reflection->isReadOnly());
        self::assertSame(['canonicalBytes','artifactSha256','graphIdentity','graphSha256','nodeCount','relationCount','inspection'], array_values(array_map(static fn($m): string => $m->getName(), array_filter($reflection->getMethods(\ReflectionMethod::IS_PUBLIC), static fn($m): bool => '__construct' !== $m->getName()))));
    }
}
