<?php

declare(strict_types=1);

namespace Kongono\ApplicationGraphArtifact\Tests\Unit\Identity;

use Kongono\ApplicationGraphArtifact\Exception\InvalidApplicationGraphArtifactException;
use Kongono\ApplicationGraphArtifact\Identity\GraphIdentity;
use PHPUnit\Framework\TestCase;

final class GraphIdentityTest extends TestCase
{
    public function testExactDigestAndPrefixArePreserved(): void
    {
        $digest = str_repeat('a', 64);
        $identity = new GraphIdentity($digest);
        self::assertSame($digest, $identity->sha256());
        self::assertSame('kgn:graph:v1:graph:' . $digest, $identity->value());
    }

    public function testMalformedDigestsFailWithoutNormalization(): void
    {
        foreach (['',str_repeat('A', 64),str_repeat('a', 63),str_repeat('g', 64)] as $value) {
            try {
                new GraphIdentity($value);
                self::fail('Invalid digest accepted.');
            } catch (InvalidApplicationGraphArtifactException $failure) {
                self::assertSame('KGN-GRAPH-1117', $failure->diagnosticId());
                if ('' !== $value) {
                    self::assertStringNotContainsString($value, (string) $failure);
                }
            }
        }
    }
}
