<?php

declare(strict_types=1);

namespace Kongono\NetworkEgressApplication\Tests\Unit\Profile;

use Kongono\NetworkEgressApplication\Exception\InvalidNetworkEgressApplicationProfileException;
use Kongono\NetworkEgressApplication\Profile\NetworkEgressApplicationProfileFactory;
use Kongono\NetworkEgressApplication\Tests\Support\NetworkEgressApplicationFactory;
use PHPUnit\Framework\TestCase;

final class NetworkEgressApplicationProfileTest extends TestCase
{
    public function testExactInputsYieldDeterministicBoundedIdentity(): void
    {
        $source = NetworkEgressApplicationFactory::policy('profile-evidence');
        $bytes = NetworkEgressApplicationFactory::artifactBytes($source);
        $factory = new NetworkEgressApplicationProfileFactory();
        $first = $factory->create($source, $bytes);
        $second = $factory->create($source, $bytes);

        self::assertTrue($first->identity()->equals($second->identity()));
        self::assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/D', $first->identity()->value());
        self::assertSame($source, $first->source());
        self::assertSame($bytes, $first->artifact()->canonicalBytes());
        self::assertStringNotContainsString('example.com', $first->identity()->value());
    }

    public function testStaleOrMismatchedArtifactFailsClosed(): void
    {
        $source = NetworkEgressApplicationFactory::policy('first-policy');
        $other = NetworkEgressApplicationFactory::policy('second-policy');

        $this->expectException(InvalidNetworkEgressApplicationProfileException::class);
        (new NetworkEgressApplicationProfileFactory())->create($source, NetworkEgressApplicationFactory::artifactBytes($other));
    }
}
