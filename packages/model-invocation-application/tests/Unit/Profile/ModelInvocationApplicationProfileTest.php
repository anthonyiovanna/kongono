<?php

declare(strict_types=1);

namespace Kongono\ModelInvocationApplication\Tests\Unit\Profile;

use Kongono\ModelInvocationApplication\Exception\InvalidModelInvocationApplicationProfileException;
use Kongono\ModelInvocationApplication\Profile\ModelInvocationApplicationProfileFactory;
use Kongono\ModelInvocationApplication\Tests\Support\ModelInvocationApplicationFactory;
use PHPUnit\Framework\TestCase;

final class ModelInvocationApplicationProfileTest extends TestCase
{
    public function testExactInputsYieldDeterministicBoundedIdentity(): void
    {
        $source = ModelInvocationApplicationFactory::source('profile-secret');
        $bytes = ModelInvocationApplicationFactory::artifactBytes($source);
        $factory = new ModelInvocationApplicationProfileFactory();
        $first = $factory->create($source, $bytes);
        $second = $factory->create($source, $bytes);

        self::assertTrue($first->identity()->equals($second->identity()));
        self::assertSame($source, $first->source());
        self::assertSame($bytes, $first->artifact()->canonicalBytes());
    }

    public function testStaleOrMismatchedArtifactFailsClosed(): void
    {
        $source = ModelInvocationApplicationFactory::source('first');
        $other = ModelInvocationApplicationFactory::source('second');

        $this->expectException(InvalidModelInvocationApplicationProfileException::class);
        (new ModelInvocationApplicationProfileFactory())->create($source, ModelInvocationApplicationFactory::artifactBytes($other));
    }
}
